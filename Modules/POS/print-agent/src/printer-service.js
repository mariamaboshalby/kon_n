'use strict';

const { execSync } = require('child_process');

/**
 * Enumerates all Windows printers installed on the current machine.
 *
 * @returns {Promise<string[]>} Array of printer names, or [] on any error.
 */
async function getInstalledPrinters() {
    try {
        const output = execSync(
            'Get-Printer | Select-Object -ExpandProperty Name',
            { encoding: 'utf8', shell: 'powershell.exe' }
        );

        return output
            .split('\n')
            .map((line) => line.trim())
            .filter((line) => line.length > 0);
    } catch (_err) {
        return [];
    }
}

module.exports = { getInstalledPrinters };
