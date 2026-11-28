<?php

namespace Modules\HR\Http\Controllers;

use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Models\HRSetting;
use Modules\HR\Services\Portal\PortalAttendanceService;
use Modules\HR\Services\Portal\PortalException;
use Modules\Progress\Models\ProjectProgress as Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Routing\Controller;

class MobileAttendanceController extends Controller
{
    /**
     * عرض صفحة البصمة مع بيانات الموظف وإعدادات الموقع
     */
    public function showAttendancePage()
    {
        $employeeId = Session::get('employee_id');

        $locationConfig = [
            'require_location' => false,
            'location_type'    => null,
            'company_location' => null,
        ];

        if ($employeeId) {
            $employee = Employee::find($employeeId);

            if ($employee) {
                $locationConfig['require_location'] = (bool) $employee->require_location;
                $locationConfig['location_type']    = $employee->location_type;

                // إحداثيات الشركة — فقط إذا نوع الموقع = company
                if ($employee->require_location && $employee->location_type === 'company') {
                    $locationConfig['company_location'] = HRSetting::getCompanyLocation();
                }
            }
        }

        return view('hr::mobile.attendance', compact('locationConfig'));
    }

    /**
     * تسجيل بصمة جديدة من الموبايل
     */
    public function recordAttendance(Request $request)
    {
       
        try {
            // الحصول على بيانات الموظف من الجلسة
            $employeeId = Session::get('employee_id');
            $fingerPrintId = Session::get('employee_finger_print_id');
            $fingerPrintName = Session::get('employee_finger_print_name');

            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسجل دخول. يرجى تسجيل الدخول أولاً'
                ], 401);
            }

            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:check_in,check_out',
                'location' => 'nullable|string',
                'notes' => 'nullable|string|max:500',
                'project_code' => 'nullable|string|max:50',
                'device_type' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            // التحقق من وجود الموظف
            $employee = Employee::find($employeeId);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'الموظف غير موجود'
                ], 404);
            }

            // التحقق من كود المشروع إذا تم إدخاله
            $projectCode = trim((string) $request->project_code);
            if ($projectCode !== '') {
                // قبول مشاريع وحدة المشاريع (is_progress=0) ومشاريع لوحة Progress معًا
                $project = Project::withoutGlobalScope('progressOnly')
                    ->where(function ($q) use ($projectCode) {
                        $q->where('project_code', $projectCode)
                            ->orWhere('name', $projectCode);
                    })
                    ->first();

                if (!$project) {
                    return response()->json([
                        'success' => false,
                        'message' => 'كود أو اسم المشروع غير موجود'
                    ], 422);
                }

                // استخدام الكود الفعلي من قاعدة البيانات
                $projectCode = $project->project_code;
            } else {
                $projectCode = null;
            }

            // استخدام وقت السيرفر بدلاً من وقت الجهاز
            $serverTime = Carbon::now(config('app.timezone'));
            $serverTimeFormatted = $serverTime->format('H:i');

            // انصراف بعد منتصف الليل (وقته أصغر من وقت آخر حضور) يتسجل
            // بتاريخ يوم الشغل — اليوم السابق — مش تاريخ التقويم
            $attendanceDate = app(PortalAttendanceService::class)
                ->workdayDateFor($employee, $serverTime, $request->type);

            // تسجيل الموقع إن وُجد دون مقارنته أو إلزام نطاق معين


            // تحويل location من JSON string إلى array
            $locationData = null;
            if ($request->location) {
                $decoded = json_decode($request->location, true);
                if ($decoded !== null) {
                    // التحقق من صحة بيانات الموقع
                    if (isset($decoded['latitude']) && isset($decoded['longitude'])) {
                        // تقريب الإحداثيات لتقليل التباين
                        $decoded['latitude'] = round($decoded['latitude'], 6);
                        $decoded['longitude'] = round($decoded['longitude'], 6);
                        
                        // إضافة معلومات إضافية
                        $decoded['captured_at'] = now()->toISOString();
                        $decoded['source'] = 'mobile_gps';
                        
                        $locationData = $decoded;
                    } else {
                        $locationData = $request->location;
                    }
                } else {
                    // إذا فشل التحويل، احفظ كـ string
                    $locationData = $request->location;
                }
            }

            // إنشاء سجل الحضور
            $attendanceData = [
                'employee_id' => $employeeId,
                'employee_attendance_finger_print_id' => $fingerPrintId,
                'employee_attendance_finger_print_name' => $fingerPrintName,
                'type' => $request->type,
                'date' => $attendanceDate,
                'time' => $serverTimeFormatted,
                'location' => $locationData,
                'status' => 'pending',
                'notes' => $request->notes,
                'project_code' => $projectCode,
                'device_type' => $request->device_type ?? 'mobile_other',
                'user_id' => null
            ];

            // التحقق من اتصال قاعدة البيانات
            try {
                DB::connection()->getPdo();
            } catch (\Exception $dbException) {
                throw $dbException;
            }

            // فرض تسلسل حضور/انصراف من السيرفر (نفس قاعدة بوابة الموظف) + منع التكرار المتزامن
            try {
                $attendance = DB::transaction(function () use ($employee, $request, $serverTime, $attendanceData) {
                    Employee::query()->whereKey($employee->id)->lockForUpdate()->first(['id']);
                    app(PortalAttendanceService::class)->assertSequence($employee, $request->type, $serverTime);

                    return Attendance::create($attendanceData);
                });
            } catch (PortalException $sequenceException) {
                return response()->json([
                    'success' => false,
                    'message' => $sequenceException->getMessage(),
                    'error_type' => $sequenceException->errorType(),
                ], $sequenceException->status());
            }

            // إرجاع النتيجة
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل البصمة بنجاح',
                'data' => [
                    'id' => $attendance->id,
                    'type' => $attendance->type,
                    'date' => $attendance->date,
                    'time' => $attendance->time,
                    'status' => $attendance->status,
                    'location' => $attendance->location,
                    'project_code' => $attendance->project_code
                ]
            ], 201);

        } catch (\Exception $e) {
            // تحديد نوع الخطأ وإعطاء رسالة مناسبة
            $errorMessage = 'حدث خطأ في تسجيل البصمة';
            if (strpos($e->getMessage(), 'Data too long') !== false) {
                $errorMessage = 'البيانات المرسلة كبيرة جداً. يرجى المحاولة مرة أخرى';
            } elseif (strpos($e->getMessage(), 'SQLSTATE') !== false) {
                $errorMessage = 'خطأ في قاعدة البيانات. يرجى المحاولة مرة أخرى';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }

    /**
     * الحصول على آخر بصمة للموظف
     */
    public function getLastAttendance(Request $request)
    {
        try {
            $employeeId = Session::get('employee_id');
            
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسجل دخول. يرجى تسجيل الدخول أولاً'
                ], 401);
            }
            
            $lastAttendance = Attendance::where('employee_id', $employeeId)
                ->latest()
                ->first();

            if (!$lastAttendance) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد بصمات سابقة',
                    'data' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $lastAttendance->id,
                    'type' => $lastAttendance->type,
                    'date' => $lastAttendance->date,
                    'time' => $lastAttendance->time,
                    'status' => $lastAttendance->status,
                    'location' => $lastAttendance->location,
                    'notes' => $lastAttendance->notes,
                    'project_code' => $lastAttendance->project_code
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب آخر بصمة'
            ], 500);
        }
    }

    /**
     * الحصول على تاريخ البصمات للموظف
     */
    public function getAttendanceHistory(Request $request)
    {
        try {
            $employeeId = Session::get('employee_id');
            
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسجل دخول. يرجى تسجيل الدخول أولاً'
                ], 401);
            }
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $limit = $request->get('limit', 50);

            $query = Attendance::where('employee_id', $employeeId);

            // فلترة حسب التاريخ
            if ($dateFrom && $dateTo) {
                $query->whereBetween('date', [$dateFrom, $dateTo]);
            } elseif ($dateFrom) {
                $query->where('date', '>=', $dateFrom);
            } elseif ($dateTo) {
                $query->where('date', '<=', $dateTo);
            }

            $attendances = $query->orderBy('date', 'desc')
                ->orderBy('time', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $attendances->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'type' => $attendance->type,
                        'date' => $attendance->date,
                        'time' => $attendance->time,
                        'status' => $attendance->status,
                        'location' => $attendance->location,
                        'notes' => $attendance->notes,
                        'project_code' => $attendance->project_code
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب تاريخ البصمات'
            ], 500);
        }
    }

    /**
     * الحصول على إحصائيات البصمات للموظف
     */
    public function getAttendanceStats(Request $request)
    {
        try {
            $employeeId = Session::get('employee_id');
            
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسجل دخول. يرجى تسجيل الدخول أولاً'
                ], 401);
            }
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

            $stats = [
                'total_attendances' => Attendance::where('employee_id', $employeeId)
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->count(),
                'check_ins' => Attendance::where('employee_id', $employeeId)
                    ->where('type', 'check_in')
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->count(),
                'check_outs' => Attendance::where('employee_id', $employeeId)
                    ->where('type', 'check_out')
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->count(),
                'pending' => Attendance::where('employee_id', $employeeId)
                    ->where('status', 'pending')
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->count(),
                'approved' => Attendance::where('employee_id', $employeeId)
                    ->where('status', 'approved')
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->count(),
                'rejected' => Attendance::where('employee_id', $employeeId)
                    ->where('status', 'rejected')
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب إحصائيات البصمات'
            ], 500);
        }
    }

    /**
     * التحقق من إمكانية تسجيل البصمة
     */
    public function canRecordAttendance(Request $request)
    {
        try {
            $employeeId = Session::get('employee_id');
            
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مسجل دخول. يرجى تسجيل الدخول أولاً'
                ], 401);
            }
            $type = $request->get('type', 'check_in');
            $today = Carbon::now(config('app.timezone'))->format('Y-m-d');

            // التحقق من آخر بصمة اليوم
            $lastAttendance = Attendance::where('employee_id', $employeeId)
                ->where('date', $today)
                ->latest()
                ->first();

            $canRecord = true;
            $message = 'يمكن تسجيل البصمة';
            $lastType = null;

            if ($lastAttendance) {
                $lastType = $lastAttendance->type;

                // منطق الشفتات:
                // - لا يجوز دخولان متتاليان بدون خروج بينهما
                // - لا يجوز خروج إذا لم يكن هناك دخول قبله
                // - يُسمح بالدخول بعد الخروج (شفت جديد أو انتقال موقع)
                if ($lastType === 'check_in' && $type === 'check_in') {
                    $canRecord = false;
                    $message = 'يجب تسجيل خروج أولاً قبل تسجيل دخول جديد';
                } elseif ($lastType === 'check_out' && $type === 'check_out') {
                    $canRecord = false;
                    $message = 'يجب تسجيل دخول أولاً قبل تسجيل خروج جديد';
                }
                // check_in بعد check_out → مسموح (شفت جديد)
                // check_out بعد check_in → مسموح (نهاية الشفت)
            } else {
                // لا توجد بصمات اليوم: لا يجوز البدء بخروج
                if ($type === 'check_out') {
                    $canRecord = false;
                    $message = 'يجب تسجيل دخول أولاً';
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'can_record' => $canRecord,
                    'message' => $message,
                    'last_type' => $lastType,
                    'last_attendance' => $lastAttendance ? [
                        'id' => $lastAttendance->id,
                        'type' => $lastAttendance->type,
                        'time' => $lastAttendance->time,
                        'status' => $lastAttendance->status
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في التحقق من إمكانية تسجيل البصمة'
            ], 500);
        }
    }

    /**
     * حساب المسافة بين نقطتين جغرافيتين بالمتر (Haversine formula)
     */
    private function haversineDistance(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $earthRadius = 6371000; // بالمتر

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
