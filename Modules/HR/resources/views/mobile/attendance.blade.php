<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>تسجيل البصمة - Massar ERP</title>
    
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--masar-font-family);
            background: linear-gradient(135deg, #1b1b22 0%, #383841 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .attendance-container {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 10px;
            padding-top: 15px;
            padding-bottom: 15px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .attendance-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 20px 18px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            margin: 0 auto;
        }
        
        .attendance-header {
            margin-bottom: 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            gap: 15px;
        }
        
        .attendance-title {
            color: #333;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
            line-height: 1.3;
            flex: 1;
        }
        
        .attendance-subtitle {
            color: #856404;
            font-size: 13px;
            line-height: 1.4;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 8px 12px;
            margin: 10px 0;
            text-align: center;
        }
        
        .user-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .user-content {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-id {
            color: #666;
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .user-details {
            font-size: 8px;
            color: #666;
            line-height: 1.2;
        }
        
        .user-details div {
            margin-bottom: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .attendance-type {
            margin-bottom: 20px;
        }
        
        .type-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .type-btn {
            flex: 1;
            padding: 14px 12px;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        
        
        .type-btn.active {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }
        
        .type-btn[data-type="check_out"].active {
            border-color: #dc3545;
            background: #dc3545;
            color: white;
        }
        
        .type-btn:hover:not(.active) {
            border-color: #28a745;
            transform: translateY(-2px);
        }
        
        .type-btn[data-type="check_out"]:hover:not(.active) {
            border-color: #dc3545;
            transform: translateY(-2px);
        }
        
        .type-icon {
            font-size: 22px;
            margin-bottom: 6px;
            display: block;
        }
        
        .type-label {
            font-size: 13px;
            font-weight: bold;
        }
        
        .attendance-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-checkin {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-checkout {
            background: #dc3545;
            color: white;
        }
        
        .attendance-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .attendance-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .logout-icon-btn {
            width: 38px;
            height: 38px;
            border: 2px solid #dc3545;
            border-radius: 50%;
            background: transparent;
            color: #dc3545;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .logout-icon-btn:hover:not(:disabled) {
            background: #dc3545;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        }
        
        .logout-icon-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .location-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 15px;
            text-align: right;
        }
        
        .location-icon {
            color: #1976d2;
            margin-left: 6px;
        }
        
        .location-text {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            line-height: 1.3;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 18px 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #28a745;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .status-message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .last-attendance {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 12px;
            margin-top: 15px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .last-attendance-title {
            font-size: 14px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .last-attendance-title::before {
            content: "📅";
            font-size: 16px;
            margin-left: 5px;
        }
        
        .last-attendance-info {
            font-size: 12px;
            color: #856404;
            line-height: 1.5;
        }
        
        /* تحسينات للشاشات الصغيرة جداً */
        @media (max-width: 360px) {
            .attendance-container {
                padding: 8px;
                padding-top: 12px;
                padding-bottom: 12px;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .attendance-card {
                padding: 18px 15px;
                border-radius: 15px;
                margin-bottom: 8px;
            }
            
            .attendance-title {
                font-size: 20px;
            }
            
            .attendance-subtitle {
                font-size: 12px;
            }
            
            .user-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .user-name {
                font-size: 15px;
            }
            
            .user-id {
                font-size: 12px;
            }
            
            .user-details {
                font-size: 10px;
            }
            
            .type-btn {
                padding: 12px 10px;
                min-height: 65px;
            }
            
            .type-icon {
                font-size: 20px;
            }
            
            .type-label {
                font-size: 12px;
            }
            
            .attendance-btn {
                padding: 14px;
                font-size: 15px;
                min-height: 45px;
            }
            
            .logout-icon-btn {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }
            
            .header-content {
                gap: 10px;
            }
        }
        
        /* تحسينات للشاشات المتوسطة */
        @media (min-width: 361px) and (max-width: 480px) {
            .attendance-container {
                padding-top: 15px;
                padding-bottom: 15px;
            }
            
            .attendance-card {
                padding: 20px 18px;
                margin-bottom: 12px;
            }
            
            .attendance-title {
                font-size: 21px;
            }
            
            .user-avatar {
                width: 65px;
                height: 65px;
                font-size: 26px;
            }
            
            .type-btn {
                padding: 13px 11px;
                min-height: 68px;
            }
            
            .attendance-btn {
                padding: 15px;
                min-height: 48px;
            }
        }
        
        /* تحسينات للشاشات الكبيرة */
        @media (min-width: 481px) {
            .attendance-container {
                padding-top: 20px;
                padding-bottom: 20px;
            }
            
            .attendance-card {
                padding: 25px 22px;
                margin-bottom: 15px;
            }
            
            .attendance-title {
                font-size: 24px;
            }
            
            .attendance-subtitle {
                font-size: 14px;
            }
            
            .user-avatar {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }
            
            .user-name {
                font-size: 18px;
            }
            
            .user-id {
                font-size: 14px;
            }
            
            .user-details {
                font-size: 12px;
            }
            
            .type-btn {
                padding: 15px;
                min-height: 75px;
            }
            
            .type-icon {
                font-size: 24px;
            }
            
            .type-label {
                font-size: 14px;
            }
            
            .attendance-btn {
                padding: 18px;
                font-size: 18px;
                min-height: 55px;
            }
        }
        
        /* تحسينات للوضع الأفقي */
        @media (max-height: 600px) and (orientation: landscape) {
            .attendance-container {
                padding: 8px;
                padding-top: 12px;
                padding-bottom: 12px;
                align-items: flex-start;
            }
            
            .attendance-card {
                padding: 12px;
                margin-bottom: 8px;
            }
            
            .attendance-header {
                margin-bottom: 15px;
            }
            
            .attendance-title {
                font-size: 18px;
                margin-bottom: 5px;
            }
            
            .attendance-subtitle {
                font-size: 12px;
            }
            
            .user-info {
                padding: 6px;
                margin-bottom: 8px;
                gap: 8px;
            }
            
            .user-avatar {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
            
            .user-name {
                font-size: 11px;
            }
            
            .user-id {
                font-size: 9px;
            }
            
            .user-details {
                font-size: 7px;
            }
            
            .attendance-type {
                margin-bottom: 15px;
            }
            
            .type-buttons {
                gap: 8px;
                margin-bottom: 12px;
            }
            
            .type-btn {
                padding: 10px 8px;
                min-height: 55px;
            }
            
            .type-icon {
                font-size: 18px;
                margin-bottom: 4px;
            }
            
            .type-label {
                font-size: 11px;
            }
            
            .attendance-btn {
                padding: 12px;
                font-size: 14px;
                margin-bottom: 10px;
                min-height: 40px;
            }
            
            .location-info {
                padding: 8px;
                margin-bottom: 12px;
            }
            
            .last-attendance {
                padding: 12px;
                margin-top: 15px;
                margin-bottom: 10px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .last-attendance-title {
                font-size: 12px;
            }
            
            .last-attendance-title::before {
                font-size: 14px;
            }
            
            .last-attendance-info {
                font-size: 10px;
            }
        }
        
        /* تحسينات للتفاعل */
        .type-btn:focus,
        .attendance-btn:focus,
        .logout-icon-btn:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
        
        /* تحسينات للخط */
        @media (max-width: 480px) {
            body {
                font-size: 14px;
            }
        }
        
        /* تحسينات إضافية للاستجابة */
        @media (max-width: 320px) {
            .attendance-container {
                padding: 6px;
                padding-top: 10px;
                padding-bottom: 10px;
            }
            
            .attendance-card {
                padding: 15px 12px;
                margin-bottom: 6px;
            }
            
            .type-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .type-btn {
                min-height: 60px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 8px;
                align-items: center;
            }
            
            .attendance-title {
                text-align: center;
            }
            
            .last-attendance {
                padding: 12px;
                margin-top: 15px;
                margin-bottom: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .last-attendance-title {
                font-size: 13px;
            }
            
            .last-attendance-title::before {
                font-size: 15px;
            }
            
            .last-attendance-info {
                font-size: 11px;
            }
        }
        
        /* تحسينات للتفاعل مع اللمس */
        .type-btn,
        .attendance-btn,
        .logout-icon-btn {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        
        .type-btn:active {
            transform: translateY(0) scale(0.98);
        }
        
        .attendance-btn:active {
            transform: translateY(0) scale(0.98);
        }
        
        .logout-icon-btn:active {
            transform: scale(0.95);
        }
        
        /* تحسينات للوضع المظلم */
        @media (prefers-color-scheme: dark) {
            .attendance-card {
                background: #1a1a1a;
                color: #ffffff;
            }
            
            .attendance-title {
                color: #ffffff;
            }
            
            .attendance-subtitle {
                color: #ffffff;
                background: #664d03;
                border: 1px solid #997404;
            }
            
            .user-info {
                background: #2a2a2a;
                border: 1px solid #444444;
            }
            
            .user-name {
                color: #ffffff;
            }
            
            .user-id {
                color: #cccccc;
            }
            
            .user-details {
                color: #cccccc;
            }
            
            .type-btn {
                background: #2a2a2a;
                border-color: #444444;
                color: #ffffff;
            }
            
            .type-btn:hover:not(.active) {
                border-color: #28a745;
                background: #2a2a2a;
            }
            
            .location-info {
                background: #2a2a2a;
                border: 1px solid #444444;
            }
            
            .last-attendance {
                background: #2a2a2a;
                border-color: #444444;
            }
            
            .last-attendance-title {
                color: #ffc107;
            }
            
            .last-attendance-info {
                color: #cccccc;
            }
        }
        
        /* تحسينات للشاشات عالية الدقة */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .user-avatar,
            .type-icon {
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
        }
        
        /* تحسينات للوصولية */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* تحسينات للشاشات الصغيرة جداً مع دعم اللمس */
        @media (max-width: 320px) and (pointer: coarse) {
            .type-btn {
                min-height: 48px;
            }
            
            .attendance-btn {
                min-height: 48px;
                font-size: 16px;
            }
            
            .logout-icon-btn {
                width: 44px;
                height: 44px;
            }
        }
        
        /* تحسينات لحقل كود المشروع */
        #project-code {
            font-family: var(--masar-font-family);
        }
        
        #project-code:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .project-code-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e9ecef;
        }
        
        /* تحسينات للشاشات الكبيرة جداً */
        @media (min-width: 768px) {
            .attendance-container {
                padding: 25px;
                padding-top: 30px;
                padding-bottom: 30px;
            }
            
            .attendance-card {
                max-width: 500px;
                padding: 35px 30px;
                margin-bottom: 15px;
            }
            
            .attendance-title {
                font-size: 28px;
            }
            
            .attendance-subtitle {
                font-size: 16px;
            }
            
            .user-avatar {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
            
            .user-name {
                font-size: 20px;
            }
            
            .user-id {
                font-size: 16px;
            }
            
            .user-details {
                font-size: 14px;
            }
            
            .type-btn {
                padding: 20px;
                min-height: 90px;
            }
            
            .type-icon {
                font-size: 28px;
            }
            
            .type-label {
                font-size: 16px;
            }
            
            .attendance-btn {
                padding: 20px;
                font-size: 20px;
                min-height: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="attendance-container">
        <div class="attendance-card">
            <div class="attendance-header">
                <div class="header-content">
                    <h1 class="attendance-title">تسجيل البصمة</h1>
                    <button class="logout-icon-btn" id="logout-btn" onclick="logoutEmployee()" title="خروج">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
                <p class="attendance-subtitle" id="attendanceSubtitle">اختر نوع البصمة وسجل حضورك</p>
            </div>
            
            <!-- معلومات المستخدم -->
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-content">
                    <div class="user-name" id="employeeName">المستخدم</div>
                    <div class="user-id">رقم الموظف: <span id="employeeId">-</span></div>
                    <div class="user-details">
                        <div>رقم البصمة: <span id="fingerPrintId">-</span></div>
                        <div>اسم البصمة: <span id="fingerPrintName">-</span></div>
                        <div>المنصب: <span id="employeePosition">-</span></div>
                        <div>القسم: <span id="employeeDepartment">-</span></div>
                    </div>
                </div>
            </div>
            
            <!-- معلومات الموقع -->
            <div class="location-info" id="location-info" style="display: none;">
                <i class="fas fa-map-marker-alt location-icon"></i>
                <span id="location-address">جاري تحديد الموقع...</span>
                <div class="location-text" id="location-coordinates"></div>
            </div>

            <!-- مؤشر نطاق الشركة / المشروع -->
            <div id="geofence-status" style="display:none; border-radius:10px; padding:10px 12px; margin-bottom:15px; font-size:13px; text-align:right;"></div>
            
            <!-- معلومات الجهاز -->
            <div class="device-info" style="
                background: #e8f5e9; 
                border: 1px solid #c8e6c9; 
                border-radius: 10px; 
                padding: 12px; 
                margin-bottom: 15px; 
                text-align: right;
            " id="device-info" style="display: none;">
                <i class="fas fa-mobile-alt" style="color: #4caf50; margin-left: 8px;"></i>
                <span id="device-name" style="font-weight: bold; color: #2e7d32;">جاري كشف الجهاز...</span>
                <div class="location-text" id="device-details" style="margin-top: 4px; font-size: 11px; color: #555;"></div>
            </div>
            
            <!-- رسالة توضيحية للموقع -->
            <div class="location-help" style="
                background: #fff3cd; 
                border: 1px solid #ffeaa7; 
                border-radius: 8px; 
                padding: 10px; 
                margin-bottom: 15px; 
                font-size: 12px; 
                color: #856404;
                text-align: right;
                display: none;
            " id="location-help">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>تنبيه مهم:</strong> تحديد الموقع إجباري لتسجيل البصمة. يرجى السماح بالوصول  للموقع في إعدادات المتصفح و الهاتف. 
                إذا كنت تستخدم HTTP، قد تحتاج إلى استخدام HTTPS لتحديد الموقع.
            </div>
            
            <!-- نوع البصمة -->
            <div class="attendance-type">
                <div class="type-buttons">
                    <div class="type-btn active" data-type="check_in">
                        <i class="fas fa-sign-in-alt type-icon"></i>
                        <div class="type-label">بصمه دخول</div>
                    </div>
                    <div class="type-btn" data-type="check_out">
                        <i class="fas fa-sign-out-alt type-icon" style="transform: scaleX(-1);"></i>
                        <div class="type-label">بصمه خروج</div>
                    </div>
                </div>
            </div>
            
            <!-- كود المشروع -->
            <div class="project-code-section" style="margin-bottom: 15px;">
                <label for="project-code" style="
                    display: block; 
                    font-size: 13px; 
                    font-weight: bold; 
                    color: #333; 
                    margin-bottom: 8px;
                    text-align: right;
                ">كود أو اسم المشروع (اختياري)</label>
                <input type="text" 
                       id="project-code" 
                       name="project_code" 
                       placeholder="أدخل كود المشروع أو اسم المشروع..." 
                       maxlength="50"
                       style="
                           width: 100%; 
                           padding: 12px; 
                           border: 2px solid #e9ecef; 
                           border-radius: 10px; 
                           font-size: 14px; 
                           text-align: right;
                           transition: border-color 0.3s ease;
                           background: white;
                       "
                       onfocus="this.style.borderColor='#28a745'"
                       onblur="this.style.borderColor='#e9ecef'">
            </div>
            
            <!-- زر تسجيل البصمة -->
            <button class="attendance-btn btn-checkin" id="attendance-btn" onclick="recordAttendance()" disabled>
                <i class="fas fa-fingerprint"></i>
                <span>انتظار تحديد الموقع...</span>
            </button>
            
            <!-- Loading -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <div>جاري تسجيل البصمة...</div>
            </div>
            
            <!-- رسالة الحالة -->
            <div id="status-message" class="status-message" style="display: none;"></div>
            <div id="errorMessage" class="status-message status-error" style="display: none;"></div>
            
            <!-- آخر بصمة -->
            <div class="last-attendance" id="last-attendance">
                <div class="last-attendance-title">آخر بصمة</div>
                <div class="last-attendance-info" id="last-attendance-info">
                    <i class="fas fa-spinner fa-spin"></i> جاري تحميل آخر بصمة...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedType = 'check_in';
        let currentLocation = null;
        let locationTracker = null;
        let deviceType = 'desktop';

        // إعدادات الموقع الجغرافي من الـ server
        const locationConfig = @json($locationConfig ?? ['require_location'=>false,'location_type'=>null,'company_location'=>null]);
        
        // تهيئة الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            detectDeviceType();
            checkEmployeeAuth();
        });
        
        // كشف نوع الجهاز
        function detectDeviceType() {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            
            // Check for mobile devices
            if (/android/i.test(ua)) {
                deviceType = 'android';
            } else if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
                deviceType = 'ios';
            } else if (/windows phone/i.test(ua)) {
                deviceType = 'windows_phone';
            } else if (/firefox/i.test(ua) && /android/i.test(ua)) {
                deviceType = 'android_tablet';
            } else if (/iPad|iPad Simulator/i.test(ua)) {
                deviceType = 'ios_tablet';
            } else if (/webos|opera mini|opera mobi/i.test(ua)) {
                deviceType = 'mobile_other';
            } else if (/silk/i.test(ua)) {
                deviceType = 'amazon_fire';
            } else {
                // Check for tablet by screen size
                if (window.innerWidth >= 768 && window.innerWidth <= 1024) {
                    deviceType = 'tablet';
                } else {
                    deviceType = 'desktop';
                }
            }
            
            console.log('Device type detected:', deviceType);
        }
        
        // التحقق من تسجيل دخول الموظف
        async function checkEmployeeAuth() {
            try {
                const response = await fetch('/api/employee/check-auth', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (response.ok && result.success && result.data.logged_in) {
                    // الموظف مسجل دخول
                    currentEmployee = result.data.employee;
                    updateEmployeeInfo();
                    initializePage();
                    setupEventListeners();
                    getCurrentLocation();
                    loadLastAttendance();
                } else {
                    // الموظف غير مسجل دخول
                    showError('يرجى تسجيل الدخول أولاً');
                    setTimeout(() => {
                        window.location.href = '/mobile/employee-login';
                    }, 2000);
                }
                
            } catch (error) {
                console.error('خطأ في التحقق من تسجيل الدخول:', error);
                showError('خطأ في الاتصال. يرجى المحاولة مرة أخرى');
                setTimeout(() => {
                    window.location.href = '/mobile/employee-login';
                }, 2000);
            }
        }
        
        function initializePage() {
            // إخفاء شريط التنقل في المتصفح
            if (window.navigator.standalone === true) {
                document.body.classList.add('standalone');
            }
            // لا نمنع التمرير — الصفحة تحتاج scroll على الموبايل
        }
        
        function setupEventListeners() {
            // تغيير نوع البصمة
            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // إزالة active من جميع الأزرار
                    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                    
                    // إضافة active للزر المحدد
                    this.classList.add('active');
                    
                    // تحديث النوع المحدد
                    selectedType = this.dataset.type;
                    
                    // تحديث زر التسجيل
                    updateAttendanceButton();

                    // التحقق من إمكانية تسجيل النوع المختار (شفتات)
                    checkShiftStatus(selectedType);
                });
            });
        }

        // التحقق من حالة الشفت وتحديث واجهة المستخدم
        async function checkShiftStatus(type) {
            try {
                const response = await fetch(`/api/attendance/can-record?type=${type}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                const subtitleEl = document.getElementById('attendanceSubtitle');

                if (response.ok && result.success) {
                    const { can_record, message, last_type } = result.data;

                    if (!can_record) {
                        // أظهر تحذير في العنوان الفرعي
                        subtitleEl.textContent = message;
                        subtitleEl.style.background = '#f8d7da';
                        subtitleEl.style.borderColor = '#f5c6cb';
                        subtitleEl.style.color = '#721c24';

                        // عطّل زر التسجيل
                        const btn = document.getElementById('attendance-btn');
                        if (!btn.disabled) {
                            btn.disabled = true;
                            btn.style.opacity = '0.5';
                        }
                    } else {
                        // إعادة الألوان الطبيعية
                        if (last_type === 'check_out' && type === 'check_in') {
                            subtitleEl.textContent = 'دخول شفت جديد — سجّل حضورك';
                        } else if (last_type === null) {
                            subtitleEl.textContent = 'اختر نوع البصمة وسجل حضورك';
                        } else {
                            subtitleEl.textContent = 'اختر نوع البصمة وسجل حضورك';
                        }
                        subtitleEl.style.background = '#fff3cd';
                        subtitleEl.style.borderColor = '#ffeaa7';
                        subtitleEl.style.color = '#856404';

                        // أعد تفعيل الزر إذا كان الموقع محدداً
                        if (currentLocation) {
                            const btn = document.getElementById('attendance-btn');
                            btn.disabled = false;
                            btn.style.opacity = '1';
                        }
                    }
                }
            } catch (error) {
                console.warn('تعذّر التحقق من حالة الشفت:', error);
            }
        }
        
        function updateAttendanceButton() {
            const btn = document.getElementById('attendance-btn');
            
            if (selectedType === 'check_in') {
                btn.className = 'attendance-btn btn-checkin';
                btn.innerHTML = '<i class="fas fa-fingerprint"></i><span>تسجيل دخول</span>';
            } else {
                btn.className = 'attendance-btn btn-checkout';
                btn.innerHTML = '<i class="fas fa-fingerprint"></i><span>تسجيل خروج</span>';
            }
        }
        
        // تحديث معلومات الموظف
        function updateEmployeeInfo() {
            if (currentEmployee) {
                document.getElementById('employeeName').textContent = currentEmployee.name;
                document.getElementById('employeeId').textContent = currentEmployee.id;
                document.getElementById('fingerPrintId').textContent = currentEmployee.finger_print_id;
                document.getElementById('fingerPrintName').textContent = currentEmployee.finger_print_name;
                document.getElementById('employeePosition').textContent = currentEmployee.position || 'غير محدد';
                document.getElementById('employeeDepartment').textContent = currentEmployee.department?.name || 'غير محدد';
            }
        }
        
        // دالة للحصول على موقع أكثر دقة
        async function getAccurateLocation() {
            return new Promise((resolve, reject) => {
                let attempts = 0;
                const maxAttempts = 3;
                const minAccuracy = 200; // دقة مقبولة بالمتر (أكثر واقعية)
                
                function tryGetLocation() {
                    attempts++;
                    
                    const options = {
                        enableHighAccuracy: attempts === 1, // المحاولة الأولى دقة عالية، الثانية عادية
                        timeout: 15000,
                        maximumAge: 0
                    };
                    
                    console.log(`محاولة تحديد الموقع (${attempts}): enableHighAccuracy=${options.enableHighAccuracy}`);
                    
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            // التحقق من دقة الموقع
                            if (position.coords.accuracy <= minAccuracy || attempts >= maxAttempts) {
                                console.log(`الموقع محدد بدقة: ${position.coords.accuracy}m (محاولة ${attempts})`);
                                resolve(position);
                            } else {
                                console.log(`دقة الموقع ضعيفة: ${position.coords.accuracy}m (محاولة ${attempts})`);
                                if (attempts < maxAttempts) {
                                    setTimeout(tryGetLocation, 1000);
                                } else {
                                    resolve(position);
                                }
                            }
                        },
                        (error) => {
                            console.warn(`فشل محاولة ${attempts}:`, error.message);
                            if (attempts < maxAttempts) {
                                setTimeout(tryGetLocation, 1000);
                            } else {
                                reject(error);
                            }
                        },
                        options
                    );
                }
                
                tryGetLocation();
            });
        }

        // ── مؤشر نطاق الشركة / المشروع ──────────────────────────────────────
        function haversineMeters(lat1, lng1, lat2, lng2) {
            const R = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat/2)**2
                    + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2)**2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        function updateGeofenceStatus() {
            const el = document.getElementById('geofence-status');
            if (el) el.style.display = 'none';
        }
        // ─────────────────────────────────────────────────────────────────────

        async function getCurrentLocation() {
            try {
                if (navigator.permissions && navigator.permissions.query) {
                    const permission = await navigator.permissions.query({ name: 'geolocation' });
                    if (permission.state === 'denied') {
                        throw new Error('إذن الموقع محظور لهذا الموقع في Chrome. افتحي إعدادات الموقع من رمز القفل واختاري Location: Allow ثم أعيدي تحميل الصفحة.');
                    }
                }

                if (!navigator.geolocation) {
                    throw new Error('المتصفح لا يدعم تحديد الموقع');
                }
                
                // فحص إذا كان الموقع يستخدم HTTPS
                if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                    console.warn('تحديد الموقع يتطلب HTTPS في الإنتاج');
                }
                
                // إظهار رسالة تحميل
                showLocationLoading();
                
                // محاولة الحصول على موقع أكثر دقة
                const position = await getAccurateLocation();
                
                currentLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                
                // إخفاء رسالة المساعدة عند نجاح تحديد الموقع
                document.getElementById('location-help').style.display = 'none';
                
                // الحصول على العنوان من Google Maps
                await getAddressFromCoordinates(currentLocation.latitude, currentLocation.longitude);
                
                // إظهار معلومات الموقع
                document.getElementById('location-info').style.display = 'block';
                
                // إظهار معلومات الجهاز
                showDeviceInfo();
                
                // تفعيل زر تسجيل البصمة
                enableAttendanceButton();

                // تحديث مؤشر النطاق الجغرافي
                updateGeofenceStatus();

                // التحقق من حالة الشفت بعد تحديد الموقع
                checkShiftStatus(selectedType);
                
            } catch (error) {
                console.error('خطأ في تحديد الموقع:', error);
                showLocationError(error);
                disableAttendanceButton();
            }
        }
        
        async function getAddressFromCoordinates(lat, lng) {
            const roundedLat = Math.round(lat * 1000000) / 1000000;
            const roundedLng = Math.round(lng * 1000000) / 1000000;
            const apiKey = @json(config('services.google_maps.api_key'));
            let address = null;

            // محاولة Google Maps اولاً
            if (apiKey) {
                try {
                    const response = await fetch(
                        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${roundedLat},${roundedLng}&key=${apiKey}&language=ar&result_type=street_address|route|locality|administrative_area_level_1|country`
                    );
                    if (!response.ok) throw new Error('Google Maps HTTP ' + response.status);
                    const data = await response.json();
                    if (data.status === 'OK' && data.results.length > 0) {
                        address = data.results[0].formatted_address;
                        if (address.length > 100) {
                            for (let i = 1; i < data.results.length && i < 3; i++) {
                                if (data.results[i].formatted_address.length <= 100) {
                                    address = data.results[i].formatted_address;
                                    break;
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Google Maps error:', e);
                }
            }

            // Fallback: Nominatim (OpenStreetMap)
            if (!address) {
                try {
                    const r = await fetch(
                        'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=' + roundedLat + '&lon=' + roundedLng + '&accept-language=ar',
                        { headers: { 'Accept': 'application/json' } }
                    );
                    if (!r.ok) throw new Error('Nominatim HTTP ' + r.status);
                    const d = await r.json();
                    const p = d.address || {};
                    const street = [p.house_number, p.road].filter(Boolean).join(' ');
                    const parts = [
                        street,
                        p.neighbourhood || p.suburb || p.quarter,
                        p.city || p.town || p.village || p.municipality,
                        p.state || p.governorate,
                        p.country
                    ].filter(Boolean);
                    address = [...new Set(parts)].join('، ') || d.display_name || null;
                } catch (e) {
                    console.warn('Nominatim error:', e);
                }
            }

            // تحديث currentLocation وعرض العنوان
            currentLocation.latitude  = roundedLat;
            currentLocation.longitude = roundedLng;
            currentLocation.address   = address;

            document.getElementById('location-address').textContent =
                address || 'الموقع محدد بنجاح';
            document.getElementById('location-coordinates').textContent =
                'دقة تحديد الموقع: ' + currentLocation.accuracy.toFixed(1) + ' متر';
        }
                function showLocationLoading() {
            document.getElementById('location-info').style.display = 'block';
            document.getElementById('location-address').innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري تحديد الموقع...';
            document.getElementById('location-coordinates').textContent = 'يرجى الانتظار...';
            
            // تعطيل زر تسجيل البصمة أثناء تحديد الموقع
            disableAttendanceButton();
        }
        
        function showLocationError(error) {
            document.getElementById('location-info').style.display = 'block';
            document.getElementById('location-help').style.display = 'block';
            
            // تعطيل زر تسجيل البصمة عند فشل تحديد الموقع
            disableAttendanceButton();
            
            let errorMessage = 'خطأ في تحديد الموقع';
            let errorDetails = 'تحديد الموقع إجباري لتسجيل البصمة';
            
            if (error) {
                const isSecure = window.isSecureContext;
                const protocol = window.location.protocol;
                
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'تم رفض الوصول للموقع';
                        errorDetails = 'يرجى السماح بالوصول للموقع في إعدادات المتصفح والجهاز. الموقع إجباري لتسجيل البصمة.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'الموقع غير متاح';
                        errorDetails = 'تأكد من تفعيل خدمة الموقع (GPS) في جهازك.';
                        if (!isSecure && protocol === 'http:') {
                            errorDetails += '<br><strong style="color:red">تنبيه: المتصفحات تمنع الوصول للموقع عبر HTTP على الموبايل. يرجى استخدام HTTPS.</strong>';
                        }
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'انتهت مهلة تحديد الموقع';
                        errorDetails = 'استغرق تحديد الموقع وقتاً طويلاً. يرجى التأكد من وجود إشارة GPS جيدة والمحاولة مرة أخرى.';
                        break;
                    default:
                        errorMessage = 'خطأ في تحديد الموقع';
                        errorDetails = error.message || 'تحديد الموقع إجباري لتسجيل البصمة';
                        break;
                }
                
                // إضافة معلومات تقنية للمساعدة في التشخيص
                errorDetails += `<br><small style="opacity: 0.7">المتصفح: ${!isSecure ? 'بيئة غير آمنة' : 'بيئة آمنة'} | البروتوكول: ${protocol}</small>`;
            }
            
            document.getElementById('location-address').innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> ${errorMessage}
                <br><button onclick="retryLocation()" style="
                    background: #dc3545; 
                    color: white; 
                    border: none; 
                    padding: 5px 15px; 
                    border-radius: 8px; 
                    margin-top: 10px; 
                    font-size: 13px;
                    font-weight: bold;
                    cursor: pointer;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                ">إعادة المحاولة</button>
            `;
            document.getElementById('location-coordinates').innerHTML = errorDetails;
        }
        
        // عرض معلومات الجهاز
        function showDeviceInfo() {
            const deviceInfoDiv = document.getElementById('device-info');
            const deviceNameSpan = document.getElementById('device-name');
            const deviceDetailsDiv = document.getElementById('device-details');
            
            if (!deviceInfoDiv || !deviceNameSpan || !deviceDetailsDiv) return;
            
            // تحديد اسم الجهاز بناءً على نوع الجهاز
            let deviceName = '';
            let deviceDetails = '';
            
            switch (deviceType) {
                case 'android':
                    deviceName = 'جهاز أندرويد';
                    deviceDetails = 'نظام: Android | المتصفح: ' + getBrowserName();
                    break;
                case 'ios':
                    deviceName = 'جهاز iPhone';
                    deviceDetails = 'نظام: iOS | المتصفح: ' + getBrowserName();
                    break;
                case 'ios_tablet':
                    deviceName = 'جهاز iPad';
                    deviceDetails = 'نظام: iOS | المتصفح: ' + getBrowserName();
                    break;
                case 'android_tablet':
                    deviceName = 'جهاز تابلت أندرويد';
                    deviceDetails = 'نظام: Android | المتصفح: ' + getBrowserName();
                    break;
                case 'windows_phone':
                    deviceName = 'جهاز ويندوز فون';
                    deviceDetails = 'نظام: Windows Phone | المتصفح: ' + getBrowserName();
                    break;
                case 'tablet':
                    deviceName = 'جهاز تابلت';
                    deviceDetails = 'نظام: غير معروف | المتصفح: ' + getBrowserName();
                    break;
                case 'desktop':
                    deviceName = 'كمبيوتر مكتبي';
                    deviceDetails = 'نظام: Desktop | المتصفح: ' + getBrowserName();
                    break;
                case 'mobile_other':
                    deviceName = 'جهاز موبايل';
                    deviceDetails = 'نظام: Mobile | المتصفح: ' + getBrowserName();
                    break;
                case 'amazon_fire':
                    deviceName = 'جهاز Amazon Fire';
                    deviceDetails = 'نظام: Fire OS | المتصفح: Silk';
                    break;
                default:
                    deviceName = 'جهاز غير معروف';
                    deviceDetails = 'نظام: Unknown | المتصفح: ' + getBrowserName();
            }
            
            deviceNameSpan.textContent = deviceName;
            deviceDetailsDiv.textContent = deviceDetails;
            deviceInfoDiv.style.display = 'block';
        }
        
        // كشف اسم المتصفح
        function getBrowserName() {
            const ua = navigator.userAgent;
            if (ua.indexOf('Firefox') > -1) return 'Firefox';
            if (ua.indexOf('SamsungBrowser') > -1) return 'Samsung Browser';
            if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) return 'Opera';
            if (ua.indexOf('Edg') > -1) return 'Edge';
            if (ua.indexOf('Chrome') > -1) return 'Chrome';
            if (ua.indexOf('Safari') > -1) return 'Safari';
            if (ua.indexOf('MSIE') > -1 || ua.indexOf('Trident') > -1) return 'Internet Explorer';
            return 'Unknown';
        }
        
        function retryLocation() {
            getCurrentLocation();
        }
        
        function enableAttendanceButton() {
            const btn = document.getElementById('attendance-btn');
            btn.disabled = false;
            btn.style.opacity = '1';
            updateAttendanceButton();
        }
        
        function disableAttendanceButton() {
            const btn = document.getElementById('attendance-btn');
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.innerHTML = '<i class="fas fa-fingerprint"></i><span>انتظار تحديد الموقع...</span>';
        }
        
        // دالة للتحقق من صحة الموقع
        function validateLocation(location) {
            if (!location || !location.latitude || !location.longitude) {
                return false;
            }
            
            // التحقق من صحة الإحداثيات
            if (location.latitude < -90 || location.latitude > 90) {
                return false;
            }
            
            if (location.longitude < -180 || location.longitude > 180) {
                return false;
            }
            
            // تحذير إذا كانت الدقة ضعيفة ولكن التسجيل مسموح به دائماً
            if (location.accuracy > 500) {
                console.warn(`دقة الموقع ضعيفة جداً: ${location.accuracy}m - سيتم التسجيل مع تحذير`);
            } else if (location.accuracy > 100) {
                console.warn(`دقة الموقع ضعيفة ولكن مقبولة: ${location.accuracy}m`);
            }
            
            return true;
        }
        
        async function recordAttendance() {
            if (!currentLocation) {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'تحديد الموقع إجباري',
                    text: 'لا يمكن تسجيل البصمة بدون تحديد الموقع. هل تريد المحاولة مرة أخرى؟',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، حدد الموقع',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d'
                });
                
                if (result.isConfirmed) {
                    // محاولة تحديد الموقع
                    await getCurrentLocation();
                    if (!currentLocation) {
                        // إذا فشل تحديد الموقع مرة أخرى، منع التسجيل
                        Swal.fire({
                            icon: 'error',
                            title: 'لا يمكن التسجيل',
                            text: 'تحديد الموقع إجباري لتسجيل البصمة. يرجى المحاولة لاحقاً أو التحقق من إعدادات الموقع.',
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#dc3545'
                        });
                        return;
                    }
                } else {
                    return; // المستخدم اختار الإلغاء
                }
            }

            // التحقق من إمكانية التسجيل (منطق الشفتات) قبل الإرسال
            try {
                const canRecordResponse = await fetch(`/api/attendance/can-record?type=${selectedType}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const canRecordResult = await canRecordResponse.json();

                if (canRecordResponse.ok && canRecordResult.success && !canRecordResult.data.can_record) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'لا يمكن التسجيل',
                        text: canRecordResult.data.message,
                        confirmButtonText: 'حسناً',
                        confirmButtonColor: '#f59e0b'
                    });
                    // تحديث الواجهة لتعكس الحالة الحالية
                    checkShiftStatus(selectedType);
                    return;
                }
            } catch (checkError) {
                console.warn('تعذّر التحقق من الشفت قبل التسجيل:', checkError);
                // نكمل التسجيل — الخادم سيرد برسالة الخطأ المناسبة
            }
            
            // إظهار Loading
            showLoading(true);
            
            try {
                // التحقق من صحة الموقع
                validateLocation(currentLocation);
                
                // إعداد البيانات - الموقع إجباري
                const projectCode = document.getElementById('project-code').value.trim();
                const attendanceData = {
                    type: selectedType,
                    location: JSON.stringify({
                        latitude: currentLocation.latitude,
                        longitude: currentLocation.longitude,
                        accuracy: currentLocation.accuracy,
                        address: currentLocation.address || null,
                        timestamp: new Date().toISOString()
                    }),
                    notes: 'تم التسجيل من الموبايل مع تحديد الموقع',
                    project_code: projectCode || null,
                    device_type: deviceType
                };
                
                // إرسال البيانات للخادم
                const response = await fetch('/api/attendance/record', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(attendanceData)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    // نجح التسجيل
                    showSuccessMessage(result.message);
                    loadLastAttendance();
                    
                    // إظهار رسالة نجاح
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح!',
                        text: result.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                } else {
                    // فشل التسجيل
                    throw new Error(result.message || 'حدث خطأ في تسجيل البصمة');
                }
                
            } catch (error) {
                console.error('خطأ في تسجيل البصمة:', error);
                
                showErrorMessage(error.message);
                
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: error.message,
                    confirmButtonText: 'حسناً'
                });
            } finally {
                showLoading(false);
            }
        }
        
        function showLoading(show) {
            const loading = document.getElementById('loading');
            const btn = document.getElementById('attendance-btn');
            const logoutBtn = document.getElementById('logout-btn');
            
            if (show) {
                loading.style.display = 'block';
                btn.disabled = true;
                btn.style.opacity = '0.6';
                if (logoutBtn) {
                    logoutBtn.disabled = true;
                    logoutBtn.style.opacity = '0.6';
                }
            } else {
                loading.style.display = 'none';
                btn.disabled = false;
                btn.style.opacity = '1';
                if (logoutBtn) {
                    logoutBtn.disabled = false;
                    logoutBtn.style.opacity = '1';
                }
            }
        }
        
        function showSuccessMessage(message) {
            const statusDiv = document.getElementById('status-message');
            statusDiv.className = 'status-message status-success';
            statusDiv.textContent = message;
            statusDiv.style.display = 'block';
            
            // إخفاء الرسالة بعد 5 ثواني
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 5000);
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
                
                // إخفاء الرسالة بعد 5 ثواني
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            }
        }
        
        function showErrorMessage(message) {
            showError(message);
        }
        
        async function loadLastAttendance() {
            try {
                const response = await fetch('/api/attendance/last', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                const lastAttendanceInfo = document.getElementById('last-attendance-info');
                
                if (response.ok && result.success && result.data) {
                    const attendance = result.data;
                    
                    const typeText = attendance.type === 'check_in' ? '🟢 دخول' : '🔴 خروج';
                    const date = new Date(attendance.date).toLocaleDateString('en-GB');
                    const time = attendance.time;
                    
                    // التحقق من وجود الموقع والعنوان
                    let locationText = 'غير محدد';
                    if (attendance.location) {
                        if (typeof attendance.location === 'string') {
                            try {
                                const locationData = JSON.parse(attendance.location);
                                locationText = locationData.address || 'موقع محدد';
                            } catch (e) {
                                locationText = 'موقع محدد';
                            }
                        } else if (attendance.location && typeof attendance.location === 'object') {
                            locationText = attendance.location.address || 'موقع محدد';
                        } else {
                            locationText = 'موقع محدد';
                        }
                    }
                    
                    const projectCode = attendance.project_code
                        ? `<br><small>📋 كود المشروع: ${attendance.project_code}</small>`
                        : '';

                    // توجيه المستخدم للخطوة التالية
                    let nextAction = '';
                    if (attendance.type === 'check_in') {
                        nextAction = `<div style="margin-top:6px;padding:5px 8px;background:#fff3cd;border-radius:6px;font-size:11px;color:#856404;">
                            ⏳ أنت داخل حالياً — سجّل <strong>خروج</strong> عند المغادرة
                        </div>`;
                        // اضبط الزر الافتراضي على "خروج"
                        document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                        const checkOutBtn = document.querySelector('.type-btn[data-type="check_out"]');
                        if (checkOutBtn) {
                            checkOutBtn.classList.add('active');
                            selectedType = 'check_out';
                            updateAttendanceButton();
                        }
                    } else {
                        nextAction = `<div style="margin-top:6px;padding:5px 8px;background:#d4edda;border-radius:6px;font-size:11px;color:#155724;">
                            ✅ شفت منتهي — يمكنك تسجيل <strong>دخول شفت جديد</strong> متى شئت
                        </div>`;
                        // اضبط الزر الافتراضي على "دخول"
                        document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                        const checkInBtn = document.querySelector('.type-btn[data-type="check_in"]');
                        if (checkInBtn) {
                            checkInBtn.classList.add('active');
                            selectedType = 'check_in';
                            updateAttendanceButton();
                        }
                    }

                    lastAttendanceInfo.innerHTML = `
                        <strong>${typeText}</strong> — ${date} في ${time}<br>
                        <small>📍 الموقع: ${locationText}</small><br>
                        <small>الحالة: ${getStatusText(attendance.status)}</small>${projectCode}
                        ${nextAction}
                    `;

                    // تحديث حالة الشفت في الواجهة
                    checkShiftStatus(selectedType);

                } else {
                    // لا توجد بصمة سابقة
                    lastAttendanceInfo.innerHTML = `
                        <i class="fas fa-info-circle" style="color: #6c757d;"></i>
                        لا توجد بصمة سابقة — ابدأ بتسجيل <strong>دخول</strong>
                    `;
                    // اضبط الزر على "دخول" كافتراضي
                    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                    const checkInBtn = document.querySelector('.type-btn[data-type="check_in"]');
                    if (checkInBtn) {
                        checkInBtn.classList.add('active');
                        selectedType = 'check_in';
                        updateAttendanceButton();
                    }
                }
            } catch (error) {
                console.error('خطأ في تحميل آخر بصمة:', error);
                const lastAttendanceInfo = document.getElementById('last-attendance-info');
                lastAttendanceInfo.innerHTML = `
                    <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                    خطأ في تحميل آخر بصمة
                `;
            }
        }
        
        function getStatusText(status) {
            switch (status) {
                case 'pending': return 'قيد المراجعة';
                case 'approved': return 'معتمد';
                case 'rejected': return 'مرفوض';
                default: return status;
            }
        }
        
        // منع إغلاق الصفحة أثناء التحميل
        window.addEventListener('beforeunload', function(e) {
            const loading = document.getElementById('loading');
            if (loading.style.display === 'block') {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // تحديث الموقع كل 5 دقائق
        setInterval(getCurrentLocation, 5 * 60 * 1000);

        // تحديث مؤشر النطاق عند تغيير كود المشروع
        document.getElementById('project-code')?.addEventListener('input', updateGeofenceStatus);
        
        // دالة تسجيل خروج الموظف
        async function logoutEmployee() {
            try {
                // تأكيد من المستخدم
                const result = await Swal.fire({
                    title: 'تأكيد الخروج',
                    text: 'هل أنت متأكد من تسجيل الخروج؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، خروج',
                    cancelButtonText: 'إلغاء'
                });
                
                if (result.isConfirmed) {
                    // إظهار Loading
                    showLoading(true);
                    const logoutBtn = document.getElementById('logout-btn');
                    logoutBtn.disabled = true;
                    
                    // إرسال طلب تسجيل الخروج
                    const response = await fetch('/api/employee/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        // نجح تسجيل الخروج
                        Swal.fire({
                            icon: 'success',
                            title: 'تم تسجيل الخروج',
                            text: 'تم تسجيل خروجك بنجاح',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // الانتقال لصفحة تسجيل الدخول
                            window.location.href = '/mobile/employee-login';
                        });
                    } else {
                        throw new Error(data.message || 'حدث خطأ في تسجيل الخروج');
                    }
                }
                
            } catch (error) {
                showError('حدث خطأ في تسجيل الخروج. يرجى المحاولة مرة أخرى');
                
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ في تسجيل الخروج',
                    confirmButtonText: 'حسناً'
                });
            } finally {
                showLoading(false);
                const logoutBtn = document.getElementById('logout-btn');
                if (logoutBtn) {
                    logoutBtn.disabled = false;
                }
            }
        }
    </script>
</body>
</html>
