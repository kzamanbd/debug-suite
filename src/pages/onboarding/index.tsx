import Button from '@/components/base/button';
import Card from '@/components/base/card';
import CustomSwitch from '@/components/base/switch';
import { useToast } from '@/components/base/toast';
import { classNames } from '@/utils';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { AlertTriangle, Archive, ArrowRight, CheckCircle2, Eye, FileText, Settings, Shield, Zap } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { useNavigate } from 'react-router-dom';

interface OnboardingSettings {
    debug: string | boolean;
    debug_log: string | boolean;
    debug_display: string | boolean;
}

const steps = [
    {
        id: 1,
        title: __('Welcome', 'debug-suite'),
        subtitle: __('Get started with Debug Suite', 'debug-suite'),
        icon: Zap
    },
    {
        id: 2,
        title: __('Debug Mode', 'debug-suite'),
        subtitle: __('Configure error reporting', 'debug-suite'),
        icon: Settings
    },
    {
        id: 3,
        title: __('Debug Log', 'debug-suite'),
        subtitle: __('Set up logging', 'debug-suite'),
        icon: FileText
    }
];

const Onboarding = () => {
    const navigate = useNavigate();
    const toast = useToast();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [currentStep, setCurrentStep] = useState(1);
    const [settings, setSettings] = useState<OnboardingSettings>({
        debug: false,
        debug_log: false,
        debug_display: false
    });

    useEffect(() => {
        const checkOnboardingStatus = async () => {
            try {
                const response = await apiFetch<{
                    WP_DEBUG: boolean;
                    WP_DEBUG_LOG: boolean;
                    WP_DEBUG_DISPLAY: boolean;
                    completed: boolean;
                }>({
                    path: '/debug-suite/v1/settings?check_onboarding=true',
                    method: 'GET'
                });
                if (response.completed) {
                    void navigate('/');
                    return;
                }

                setSettings({
                    debug: response.WP_DEBUG,
                    debug_log: response.WP_DEBUG_LOG,
                    debug_display: response.WP_DEBUG_DISPLAY
                });

                setLoading(false);
            } catch (error) {
                console.error('Error checking onboarding status:', error);
                toast.error(__('Failed to check onboarding status.', 'debug-suite'));
                void navigate('/');
            }
        };

        void checkOnboardingStatus();
    }, [navigate, toast]);

    const nextStep = async () => {
        if (currentStep === steps.length) {
            setSaving(true);
            try {
                await apiFetch({
                    path: '/debug-suite/v1/settings',
                    method: 'POST',
                    data: { ...settings, onboarding_completed: true }
                });

                toast.success(__('Settings saved successfully!', 'debug-suite'));
                void navigate('/');
            } catch (error) {
                console.error('Error saving settings:', error);
                toast.error(__('Failed to save settings.', 'debug-suite'));
            } finally {
                setSaving(false);
            }
        } else {
            setCurrentStep((prev) => prev + 1);
        }
    };

    const prevStep = () => {
        setCurrentStep((prev) => prev - 1);
    };

    if (loading) {
        return (
            <div className="-m-5 flex h-screen items-center justify-center rounded-lg bg-gradient-to-br from-blue-50 to-indigo-100">
                <div className="text-center">
                    <div className="relative flex items-center justify-center">
                        <div className="border-primary h-16 w-16 animate-spin rounded-full border-4 border-t-transparent"></div>
                    </div>
                    <div className="mt-4 text-sm font-medium text-gray-600">
                        {__('Loading Debug Suite...', 'debug-suite')}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="-m-5 min-h-screen rounded-lg bg-gradient-to-br from-blue-50 via-white to-indigo-50">
            <div className="mx-auto max-w-4xl px-4 py-12">
                {/* Modern Progress Bar */}
                <div className="mb-12">
                    <div className="relative">
                        {/* Progress Line */}
                        <div className="absolute top-6 left-0 h-0.5 w-full bg-gray-200">
                            <div
                                className="bg-primary h-full transition-all duration-500 ease-out"
                                style={{ width: `${((currentStep - 1) / (steps.length - 1)) * 100}%` }}
                            />
                        </div>

                        {/* Step Indicators */}
                        <div className="grid grid-cols-3">
                            {steps.map((step, _index) => {
                                const StepIcon = step.icon;
                                const isActive = currentStep === step.id;
                                const isCompleted = currentStep > step.id;
                                const isPending = currentStep < step.id;

                                return (
                                    <div
                                        key={step.id}
                                        className={classNames(
                                            'flex flex-col items-center',
                                            step.id === 1 && 'items-start',
                                            step.id === 3 && 'items-end'
                                        )}>
                                        <div
                                            className={`relative flex h-12 w-12 transform items-center justify-center rounded-full border-2 transition-all duration-300 ease-out ${isActive ? 'border-primary bg-primary shadow-primary/25 scale-110 text-white shadow-lg' : ''} ${isCompleted ? 'border-primary bg-primary text-white' : ''} ${isPending ? 'border-gray-300 bg-white text-gray-400' : ''} `}>
                                            {isCompleted ? (
                                                <CheckCircle2 className="h-6 w-6" />
                                            ) : (
                                                <StepIcon className={`h-6 w-6 ${isActive ? 'animate-pulse' : ''}`} />
                                            )}

                                            {/* Active pulse effect */}
                                            {isActive && (
                                                <div className="border-primary absolute inset-0 animate-ping rounded-full border-2 opacity-25" />
                                            )}
                                        </div>

                                        <div className="mt-6 text-center">
                                            <div
                                                className={`text-sm font-semibold transition-colors duration-200 ${
                                                    isActive || isCompleted ? 'text-primary' : 'text-gray-500'
                                                }`}>
                                                {step.title}
                                            </div>
                                            <div
                                                className={`text-xs transition-colors duration-200 ${
                                                    isActive ? 'text-gray-600' : 'text-gray-400'
                                                }`}>
                                                {step.subtitle}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Main Content Card */}
                <div className="relative">
                    <Card className="overflow-hidden border-0 bg-white/80 shadow-2xl shadow-gray-900/10 backdrop-blur-sm">
                        <div className="relative">
                            {/* Step 1: Welcome */}
                            {currentStep === 1 && (
                                <div className="p-12 text-center">
                                    <div className="relative mb-8">
                                        <div className="from-primary to-primary/80 mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-gradient-to-br shadow-lg">
                                            <Zap className="h-12 w-12 text-white" />
                                        </div>
                                        <div className="from-primary/20 absolute -inset-2 rounded-3xl bg-gradient-to-r to-indigo-500/20 blur-xl" />
                                    </div>

                                    <h1 className="mb-6 bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-4xl font-bold text-transparent">
                                        {__('Welcome to Debug Suite!', 'debug-suite')}
                                    </h1>

                                    <div className="mx-auto mb-8 max-w-2xl text-center text-lg leading-relaxed text-gray-600">
                                        {__(
                                            "Debug Suite helps you monitor and troubleshoot your WordPress site effectively. Let's set up your debugging environment with a few simple steps.",
                                            'debug-suite'
                                        )}
                                    </div>

                                    {/* Feature Preview Cards */}
                                    <div className="mx-auto mb-8 grid max-w-3xl grid-cols-1 gap-6 md:grid-cols-3">
                                        <div className="rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 p-6 text-center">
                                            <Shield className="text-primary mx-auto mb-3 h-8 w-8" />
                                            <h3 className="mb-2 font-semibold text-gray-800">
                                                {__('Error Monitoring', 'debug-suite')}
                                            </h3>
                                            <p className="text-sm text-gray-600">
                                                {__('Track and resolve issues quickly', 'debug-suite')}
                                            </p>
                                        </div>

                                        <div className="rounded-2xl bg-gradient-to-br from-green-50 to-green-100 p-6 text-center">
                                            <Eye className="mx-auto mb-3 h-8 w-8 text-green-600" />
                                            <h3 className="mb-2 font-semibold text-gray-800">
                                                {__('Real-time Logs', 'debug-suite')}
                                            </h3>
                                            <p className="text-sm text-gray-600">
                                                {__('Monitor your site in real-time', 'debug-suite')}
                                            </p>
                                        </div>

                                        <div className="rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 p-6 text-center">
                                            <Archive className="mx-auto mb-3 h-8 w-8 text-purple-600" />
                                            <h3 className="mb-2 font-semibold text-gray-800">
                                                {__('Log Management', 'debug-suite')}
                                            </h3>
                                            <p className="text-sm text-gray-600">
                                                {__('Organize log data', 'debug-suite')}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Step 2: Debug Mode */}
                            {currentStep === 2 && (
                                <div className="p-8">
                                    <div className="mb-8 text-center">
                                        <div className="from-primary to-primary/80 mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br shadow-lg">
                                            <Settings className="h-10 w-10 text-white" />
                                        </div>
                                        <h2 className="mb-4 text-3xl font-bold text-gray-900">
                                            {__('Configure Debug Mode', 'debug-suite')}
                                        </h2>
                                        <p className="mx-auto max-w-2xl text-gray-600">
                                            {__(
                                                'WordPress Debug Mode provides detailed error reporting to help you identify and fix issues.',
                                                'debug-suite'
                                            )}
                                        </p>
                                    </div>

                                    <div className="space-y-6">
                                        {/* Main Debug Mode Card */}
                                        <div className="group rounded-2xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-6 shadow-sm transition-all duration-200 hover:shadow-md">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center space-x-4">
                                                    <div className="bg-primary/10 flex h-12 w-12 items-center justify-center rounded-xl">
                                                        <Settings className="text-primary h-6 w-6" />
                                                    </div>
                                                    <div>
                                                        <h3 className="text-lg font-semibold text-gray-900">
                                                            {__('Enable Debug Mode', 'debug-suite')}
                                                        </h3>
                                                        <p className="text-sm text-gray-600">
                                                            {__(
                                                                'Show detailed error messages and warnings',
                                                                'debug-suite'
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                                <CustomSwitch
                                                    checked={Boolean(settings.debug)}
                                                    onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                                        setSettings((prev) => ({
                                                            ...prev,
                                                            debug: event.target.checked
                                                        }))
                                                    }
                                                />
                                            </div>
                                        </div>

                                        {/* Debug Display Option - Only shown when debug mode is enabled */}
                                        {settings.debug && (
                                            <div className="animate-in slide-in-from-top-2 duration-300">
                                                <div className="rounded-2xl border border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50 p-6">
                                                    <div className="mb-4 flex items-start space-x-3">
                                                        <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-yellow-100">
                                                            <AlertTriangle className="h-5 w-5 text-yellow-600" />
                                                        </div>
                                                        <div className="flex-1">
                                                            <h3 className="text-sm font-semibold text-yellow-800">
                                                                {__('Production Warning', 'debug-suite')}
                                                            </h3>
                                                            <p className="mt-1 text-sm text-yellow-700">
                                                                {__(
                                                                    "Debug mode will display detailed error messages. While helpful during development, it's recommended to disable this in production.",
                                                                    'debug-suite'
                                                                )}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div className="rounded-xl border border-yellow-200 bg-white/60 p-4">
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex items-center space-x-3">
                                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-100">
                                                                    <Eye className="h-5 w-5 text-yellow-600" />
                                                                </div>
                                                                <div>
                                                                    <h4 className="font-medium text-gray-900">
                                                                        {__('Display Errors', 'debug-suite')}
                                                                    </h4>
                                                                    <p className="text-sm text-gray-600">
                                                                        {__(
                                                                            'Show errors directly on the screen',
                                                                            'debug-suite'
                                                                        )}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <CustomSwitch
                                                                checked={Boolean(settings.debug_display)}
                                                                onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                                                    setSettings((prev) => ({
                                                                        ...prev,
                                                                        debug_display: event.target.checked
                                                                    }))
                                                                }
                                                            />
                                                        </div>

                                                        {settings.debug_display && (
                                                            <div className="animate-in slide-in-from-top-1 mt-4 rounded-lg bg-yellow-50 p-3 duration-200">
                                                                <p className="text-sm text-yellow-700">
                                                                    {__(
                                                                        'Displaying errors on screen can expose sensitive information. Consider using debug logging instead for production environments.',
                                                                        'debug-suite'
                                                                    )}
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Step 3: Debug Log */}
                            {currentStep === 3 && (
                                <div className="p-8">
                                    <div className="mb-8 text-center">
                                        <div className="from-primary to-primary/80 mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br shadow-lg">
                                            <FileText className="h-10 w-10 text-white" />
                                        </div>
                                        <h2 className="mb-4 text-3xl font-bold text-gray-900">
                                            {__('Debug Log Setup', 'debug-suite')}
                                        </h2>
                                        <p className="mx-auto max-w-2xl text-gray-600">
                                            {__(
                                                'Configure debug logging to save error messages and warnings to a log file for later review.',
                                                'debug-suite'
                                            )}
                                        </p>
                                    </div>

                                    <div className="space-y-6">
                                        <div className="group rounded-2xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-6 shadow-sm transition-all duration-200 hover:shadow-md">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center space-x-4">
                                                    <div className="bg-primary/10 flex h-12 w-12 items-center justify-center rounded-xl">
                                                        <FileText className="text-primary h-6 w-6" />
                                                    </div>
                                                    <div>
                                                        <h3 className="text-lg font-semibold text-gray-900">
                                                            {__('Enable Debug Log', 'debug-suite')}
                                                        </h3>
                                                        <p className="text-sm text-gray-600">
                                                            {__(
                                                                'Save debug messages to a log file for analysis',
                                                                'debug-suite'
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                                <CustomSwitch
                                                    checked={Boolean(settings.debug_log)}
                                                    onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                                        setSettings((prev) => ({
                                                            ...prev,
                                                            debug_log: event.target.checked
                                                        }))
                                                    }
                                                />
                                            </div>
                                        </div>

                                        {/* Benefits section */}
                                        <div className="rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
                                            <div className="mb-4 text-xl font-semibold text-gray-900">
                                                {__('Why Enable Debug Logging?', 'debug-suite')}
                                            </div>
                                            <div className="grid gap-3 md:grid-cols-2">
                                                <div className="flex items-start space-x-3">
                                                    <CheckCircle2 className="mt-0.5 h-5 w-5 text-green-600" />
                                                    <span className="text-sm text-gray-700">
                                                        {__(
                                                            'Track errors without displaying them publicly',
                                                            'debug-suite'
                                                        )}
                                                    </span>
                                                </div>
                                                <div className="flex items-start space-x-3">
                                                    <CheckCircle2 className="mt-0.5 h-5 w-5 text-green-600" />
                                                    <span className="text-sm text-gray-700">
                                                        {__('Maintain detailed error history', 'debug-suite')}
                                                    </span>
                                                </div>
                                                <div className="flex items-start space-x-3">
                                                    <CheckCircle2 className="mt-0.5 h-5 w-5 text-green-600" />
                                                    <span className="text-sm text-gray-700">
                                                        {__('Safe for production environments', 'debug-suite')}
                                                    </span>
                                                </div>
                                                <div className="flex items-start space-x-3">
                                                    <CheckCircle2 className="mt-0.5 h-5 w-5 text-green-600" />
                                                    <span className="text-sm text-gray-700">
                                                        {__('Easy to analyze', 'debug-suite')}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Navigation */}
                            <div className="border-t bg-gray-50/50 px-8 py-6">
                                <div className="flex items-center justify-between">
                                    <Button
                                        onClick={prevStep}
                                        disabled={currentStep === 1}
                                        className={`flex items-center space-x-2 ${currentStep === 1 ? 'invisible' : ''}`}>
                                        <span>{__('Previous', 'debug-suite')}</span>
                                    </Button>

                                    <div className="text-sm text-gray-500">
                                        {__('Step', 'debug-suite')} {currentStep} {__('of', 'debug-suite')}{' '}
                                        {steps.length}
                                    </div>

                                    <Button
                                        onClick={nextStep}
                                        disabled={saving}
                                        className="flex items-center space-x-2">
                                        {saving ? (
                                            <>
                                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                                <span>{__('Saving...', 'debug-suite')}</span>
                                            </>
                                        ) : currentStep === steps.length ? (
                                            <>
                                                <CheckCircle2 className="size-4" />
                                                <span>{__('Finish Setup', 'debug-suite')}</span>
                                            </>
                                        ) : (
                                            <>
                                                <span>{__('Next', 'debug-suite')}</span>
                                                <ArrowRight className="size-4" />
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    );
};

export default Onboarding;
