import { useToast } from '@/components/ui/toast';

const DebugConfig = () => {
    const toast = useToast();
    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div className="relative cursor-not-allowed rounded-md bg-[#e83f94]/5 p-3 pl-8 opacity-80 dark:bg-[#e83f94]/10">
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            className="lucide lucide-check-circle2 size-5 text-[#e83f94]"
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8">
                        <h2 className="text-lg font-semibold">WordPress Debug Mode</h2>
                        <p className="text-muted-foreground text-sm">
                            Debug mode is enabled. This helps identify issues by showing PHP notices and warnings.
                        </p>
                        <p className="mt-1 text-xs font-medium text-[#e83f94]">Required for viewer</p>
                    </div>
                </div>
                <div className="relative cursor-not-allowed rounded-md bg-[#e83f94]/5 p-3 pl-8 opacity-80 dark:bg-[#e83f94]/10">
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            className="lucide lucide-check-circle2 size-5 text-[#e83f94]"
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8">
                        <h2 className="text-lg font-semibold">Error Logging</h2>
                        <p className="text-muted-foreground text-sm">
                            Error logging is enabled. All errors will be saved to debug.log for review.
                        </p>
                        <p className="mt-1 text-xs font-medium text-[#e83f94]">Required for viewer</p>
                    </div>
                </div>
                <div className="relative cursor-pointer rounded-md p-3 pl-8 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            className="lucide lucide-check-circle2 text-muted-foreground/30 size-5"
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8">
                        <h2 className="text-lg font-semibold">Error Display</h2>
                        <p className="text-muted-foreground text-sm">
                            Error display is disabled. Errors will be hidden from visitors.
                        </p>
                    </div>
                </div>
                <div className="relative rounded-md p-3 pl-8">
                    <div className="absolute top-3 left-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            className="lucide lucide-check-circle2 size-5 text-[#e83f94]"
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <div className="ml-8">
                        <h2 className="text-lg font-semibold">Log Viewer</h2>
                        <p className="text-muted-foreground mb-4 text-sm">
                            Log viewer is installed and ready to use. You can access it using the button below.
                        </p>
                        <div className="my-4 border-t border-gray-200 pt-2 dark:border-gray-800"></div>
                    </div>
                </div>
            </div>
            <button
                onClick={() => {
                    toast.success('This is a success message!', 3000);
                }}
                className="rounded bg-blue-500 px-4 py-2 text-white"
            >
                Click
            </button>
        </div>
    );
};

export default DebugConfig;
