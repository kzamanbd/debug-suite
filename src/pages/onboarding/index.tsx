import Button from '@/components/ui/button';
import Card from '@/components/ui/card';
import CustomSwitch from '@/components/ui/custom-switch';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { AlertTriangle, CheckCircle2, FileText, Settings, Zap } from 'lucide-react';
import type { ChangeEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';

interface OnboardingSettings {
    debug_mode: boolean;
    debug_log: boolean;
    debug_display: boolean;
}

interface OnboardingResponse {
    success: boolean;
    completed: boolean;
    settings: OnboardingSettings;
}

const steps = [
    { id: 1, title: __('Welcome', 'debug-suite') },
    { id: 2, title: __('Debug Mode', 'debug-suite') },
    { id: 3, title: __('Debug Log', 'debug-suite') }
];

const Onboarding = () => {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [currentStep, setCurrentStep] = useState(1);
    const [settings, setSettings] = useState<OnboardingSettings>({
        debug_mode: false,
        debug_log: false,
        debug_display: false
    });

    useEffect(() => {
        const checkOnboardingStatus = async () => {
            try {
                const response = await apiFetch<OnboardingResponse>({
                    path: '/debug-suite/v1/onboarding/status',
                    method: 'GET'
                });

                if (response.completed) {
                    void navigate('/');
                    return;
                }

                setSettings(response.settings);
                setLoading(false);
            } catch (error) {
                console.error('Error checking onboarding status:', error);
                toast.error(__('Failed to check onboarding status.', 'debug-suite'));
                void navigate('/');
            }
        };

        void checkOnboardingStatus();
    }, [navigate]);

    const nextStep = async () => {
        if (currentStep === steps.length) {
            setSaving(true);
            try {
                await apiFetch({
                    path: '/debug-suite/v1/onboarding/settings',
                    method: 'POST',
                    data: settings
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
            <div className="flex h-screen items-center justify-center">
                <div className="border-primary h-32 w-32 animate-spin rounded-full border-b-2"></div>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-3xl px-4 py-8">
            <div className="mb-8">
                <div className="flex items-center justify-center space-x-4">
                    {steps.map((step) => (
                        <div key={step.id} className="flex items-center">
                            <div
                                className={`flex h-10 w-10 items-center justify-center rounded-full border-2 transition-colors ${
                                    currentStep === step.id
                                        ? 'border-primary bg-primary text-white'
                                        : currentStep > step.id
                                          ? 'border-primary bg-primary/10 text-primary'
                                          : 'border-gray-300 bg-white text-gray-500'
                                }`}
                            >
                                {currentStep > step.id ? <CheckCircle2 className="h-5 w-5" /> : <span>{step.id}</span>}
                            </div>
                            {step.id !== steps.length && (
                                <div
                                    className={`h-0.5 w-16 transition-colors ${
                                        currentStep > step.id ? 'bg-primary' : 'bg-gray-300'
                                    }`}
                                ></div>
                            )}
                        </div>
                    ))}
                </div>
                <div className="mt-4 flex items-center justify-center">
                    <span className="text-sm font-medium text-gray-900">{steps[currentStep - 1].title}</span>
                </div>
            </div>

            <Card className="p-6">
                {currentStep === 1 && (
                    <div className="text-center">
                        <div className="bg-primary/10 mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full">
                            <Zap className="text-primary h-8 w-8" />
                        </div>
                        <h1 className="mb-4 text-3xl font-bold">{__('Welcome to Debug Suite!', 'debug-suite')}</h1>
                        <p className="mx-auto mb-8 max-w-2xl text-gray-600">
                            {__(
                                "Debug Suite helps you monitor and troubleshoot your WordPress site effectively. Let's set up your debugging environment with a few simple steps.",
                                'debug-suite'
                            )}
                        </p>
                    </div>
                )}

                {currentStep === 2 && (
                    <div className="space-y-6">
                        <div className="text-center">
                            <div className="bg-primary/10 mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full">
                                <Settings className="text-primary h-8 w-8" />
                            </div>
                            <h2 className="mb-4 text-2xl font-bold">{__('Configure Debug Mode', 'debug-suite')}</h2>
                            <p className="mx-auto mb-8 max-w-2xl text-gray-600">
                                {__(
                                    'WordPress Debug Mode provides detailed error reporting to help you identify and fix issues.',
                                    'debug-suite'
                                )}
                            </p>
                        </div>

                        <div className="rounded-lg border bg-white p-6 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div className="space-y-1">
                                    <h3 className="text-lg font-medium">{__('Enable Debug Mode', 'debug-suite')}</h3>
                                    <p className="text-sm text-gray-600">
                                        {__('Show detailed error messages and warnings', 'debug-suite')}
                                    </p>
                                </div>
                                <CustomSwitch
                                    checked={settings.debug_mode}
                                    onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                        setSettings((prev) => ({
                                            ...prev,
                                            debug_mode: event.target.checked
                                        }))
                                    }
                                />
                            </div>

                            {settings.debug_mode && (
                                <div className="mt-4">
                                    <div className="mb-4 rounded-lg bg-yellow-50 p-4">
                                        <div className="flex">
                                            <AlertTriangle className="h-5 w-5 text-yellow-400" />
                                            <div className="ml-3">
                                                <h3 className="text-sm font-medium text-yellow-800">
                                                    {__('Important Note', 'debug-suite')}
                                                </h3>
                                                <p className="mt-2 text-sm text-yellow-700">
                                                    {__(
                                                        "Debug mode will display detailed error messages. While helpful during development, it's recommended to disable this in production.",
                                                        'debug-suite'
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-4 border-t pt-4">
                                        <div className="flex items-center justify-between">
                                            <div className="space-y-1">
                                                <h3 className="text-lg font-medium">
                                                    {__('Display Errors', 'debug-suite')}
                                                </h3>
                                                <p className="text-sm text-gray-600">
                                                    {__('Show errors directly on the screen', 'debug-suite')}
                                                </p>
                                            </div>
                                            <CustomSwitch
                                                checked={settings.debug_display}
                                                onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                                    setSettings((prev) => ({
                                                        ...prev,
                                                        debug_display: event.target.checked
                                                    }))
                                                }
                                            />
                                        </div>
                                        {settings.debug_display && (
                                            <div className="mt-4 rounded-lg bg-yellow-50 p-4">
                                                <div className="flex">
                                                    <AlertTriangle className="h-5 w-5 text-yellow-400" />
                                                    <div className="ml-3">
                                                        <p className="text-sm text-yellow-700">
                                                            {__(
                                                                'Displaying errors on screen can expose sensitive information. Consider using debug logging instead for production environments.',
                                                                'debug-suite'
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {currentStep === 3 && (
                    <div className="space-y-6">
                        <div className="text-center">
                            <div className="bg-primary/10 mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full">
                                <FileText className="text-primary h-8 w-8" />
                            </div>
                            <h2 className="mb-4 text-2xl font-bold">{__('Debug Log Setup', 'debug-suite')}</h2>
                            <p className="mx-auto mb-8 max-w-2xl text-gray-600">
                                {__(
                                    'Configure debug logging to save error messages and warnings to a log file for later review.',
                                    'debug-suite'
                                )}
                            </p>
                        </div>

                        <div className="rounded-lg border bg-white p-6 shadow-sm">
                            <div className="flex items-center justify-between">
                                <div className="space-y-1">
                                    <h3 className="text-lg font-medium">{__('Enable Debug Log', 'debug-suite')}</h3>
                                    <p className="text-sm text-gray-600">
                                        {__('Save debug messages to a log file', 'debug-suite')}
                                    </p>
                                </div>
                                <CustomSwitch
                                    checked={settings.debug_log}
                                    onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                        setSettings((prev) => ({
                                            ...prev,
                                            debug_log: event.target.checked
                                        }))
                                    }
                                />
                            </div>
                        </div>
                    </div>
                )}

                <div className="mt-8 flex justify-between">
                    <Button
                        onClick={prevStep}
                        disabled={currentStep === 1}
                        className={`min-w-[120px] ${currentStep === 1 ? 'invisible' : ''}`}
                    >
                        {__('Previous', 'debug-suite')}
                    </Button>
                    <Button onClick={nextStep} disabled={saving} className="min-w-[120px]">
                        {saving
                            ? __('Saving...', 'debug-suite')
                            : currentStep === steps.length
                              ? __('Finish', 'debug-suite')
                              : __('Next', 'debug-suite')}
                    </Button>
                </div>
            </Card>
        </div>
    );
};

export default Onboarding;
