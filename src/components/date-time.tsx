import { dateI18n, getSettings, gmdateI18n } from '@wordpress/date';
import { RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

interface DateTimeProps {
    date: string;
    defaultDate?: any;
    localTime?: boolean;
}

function formatDateTime(date: string, type: 'date' | 'time', localTime = true): string {
    const { formats, timezone } = getSettings();
    const format = type === 'date' ? formats.date : formats.time;

    return localTime ? gmdateI18n(format, date) : dateI18n(format, date, timezone.string);
}

function DateTimeHtml({ date, defaultDate = '-', localTime = true }: DateTimeProps) {
    if (!date) {
        return defaultDate;
    }

    return (
        <RawHTML>
            {sprintf(
                // translators: %1$s: date, %2$s: time
                __('%1$s at %2$s', 'debug-suite'),
                formatDateTime(date, 'date', localTime),
                formatDateTime(date, 'time', localTime)
            )}
        </RawHTML>
    );
}

DateTimeHtml.Date = ({ date, defaultDate = '-', localTime = true }: DateTimeProps) => {
    if (!date) {
        return defaultDate;
    }

    return <RawHTML>{formatDateTime(date, 'date', localTime)}</RawHTML>;
};

DateTimeHtml.Time = ({ date: time, defaultDate = '-', localTime = true }: DateTimeProps) => {
    if (!time) {
        return defaultDate;
    }

    return <RawHTML>{formatDateTime(time, 'time', localTime)}</RawHTML>;
};

export default DateTimeHtml;
