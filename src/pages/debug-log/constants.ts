/**
 * Constants for Debug Log components.
 *
 * @since 1.0.0
 */
import { __ } from '@wordpress/i18n';

export const levelColors: Record<string, string> = {
    critical: 'bg-red-200 text-red-900 border-red-300',
    error: 'bg-red-100 text-red-800 border-red-200',
    warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    notice: 'bg-blue-100 text-blue-800 border-blue-200',
    info: 'bg-primary-100 text-primary-800 border-primary-200',
    debug: 'bg-gray-100 text-gray-800 border-gray-200'
};

export const levelOptions = [
    { value: '', label: __('All Levels', 'debug-suite') },
    { value: 'critical', label: __('Critical', 'debug-suite') },
    { value: 'error', label: __('Error', 'debug-suite') },
    { value: 'warning', label: __('Warning', 'debug-suite') },
    { value: 'notice', label: __('Notice', 'debug-suite') },
    { value: 'info', label: __('Info', 'debug-suite') },
    { value: 'debug', label: __('Debug', 'debug-suite') }
];

export const perPageOptions = [
    { value: '100', label: __('100 items', 'debug-suite') },
    { value: '250', label: __('250 items', 'debug-suite') },
    { value: '500', label: __('500 items', 'debug-suite') },
    { value: '1000', label: __('1000 items', 'debug-suite') }
];

export const sortOptions = [
    { value: 'timestamp', label: __('Sort by Date', 'debug-suite') },
    { value: 'level', label: __('Sort by Level', 'debug-suite') }
];

export const exportOptions = [
    { value: 'json', label: __('Export as JSON', 'debug-suite') },
    { value: 'csv', label: __('Export as CSV', 'debug-suite') },
    { value: 'txt', label: __('Export as TXT', 'debug-suite') }
];
