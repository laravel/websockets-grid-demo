import { Form, Head } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { update as gridUpdate } from 'App/Http/Controllers/GridController';
import { useEffect, useState } from 'react';

declare global {
    interface Window {
        Echo: any;
    }
}

const EMOJIS = ['🚀', '❤️', '🤯', '🔥'];
const GRID_COLUMNS = 10;
const GRID_ROWS = 10;
const GRID_SIZE = GRID_COLUMNS * GRID_ROWS;

interface Props {
    initialCells: Record<number, string>;
    cellTimestamps: Record<number, number>;
    cooldownSeconds: number;
}

export default function Grid({ initialCells, cellTimestamps: initialTimestamps, cooldownSeconds }: Props) {
    const [cells, setCells] = useState<Record<number, string | null>>(initialCells);
    const [selectedEmoji, setSelectedEmoji] = useState(EMOJIS[0]);
    const [timestamps, setTimestamps] = useState<Record<number, number>>(initialTimestamps);
    const [cooldowns, setCooldowns] = useState<Record<number, number>>({});

    useEffect(() => {
        const interval = setInterval(() => {
            const now = Math.floor(Date.now() / 1000);
            const newCooldowns: Record<number, number> = {};

            Object.entries(timestamps).forEach(([pos, timestamp]) => {
                const remaining = cooldownSeconds - (now - timestamp);
                if (remaining > 0) {
                    newCooldowns[parseInt(pos)] = Math.ceil(remaining);
                }
            });

            setCooldowns(newCooldowns);
        }, 100);

        return () => clearInterval(interval);
    }, [timestamps, cooldownSeconds]);

    useEchoPublic('grid', '.cell-updated', (data: { position: number; emoji: string; timestamp: number }) => {
        setCells((prev) => ({
            ...prev,
            [data.position]: data.emoji,
        }));
        setTimestamps((prev) => ({
            ...prev,
            [data.position]: data.timestamp,
        }));
    });

    return (
        <>
            <Head title="Emoji Grid" />
            <div className="flex min-h-screen flex-col bg-white dark:bg-black">
                <div className="border-b border-gray-200 bg-gray-50 px-4 py-4 sm:px-6 sm:py-6 dark:border-gray-800 dark:bg-gray-900">
                    <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h1 className="text-2xl font-bold sm:text-3xl dark:text-white">Emoji Grid</h1>
                            <p className="text-xs text-gray-600 sm:text-sm dark:text-gray-400">Real-time collaborative grid — click to add emojis</p>
                        </div>
                        <div className="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4">
                            <label htmlFor="emoji-select" className="text-sm font-medium dark:text-white">
                                Select:
                            </label>
                            <select
                                id="emoji-select"
                                value={selectedEmoji}
                                onChange={(e) => setSelectedEmoji(e.target.value)}
                                className="cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1 text-xl sm:px-4 sm:py-2 sm:text-2xl dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            >
                                {EMOJIS.map((emoji) => (
                                    <option key={emoji} value={emoji}>
                                        {emoji}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                <div className="flex flex-1 items-center justify-center overflow-auto">
                    <div
                        className="grid gap-0 rounded-lg border border-gray-300 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
                        style={{
                            gridTemplateColumns: `repeat(${GRID_COLUMNS}, minmax(0, 1fr))`,
                            width: '100vw',
                            height: '100vw',
                            maxWidth: '800px',
                            maxHeight: '800px',
                            aspectRatio: '1 / 1',
                        }}
                    >
                        {Array.from({ length: GRID_SIZE }).map((_, position) => {
                            const cooldown = cooldowns[position];
                            const isDisabled = !!cooldown;

                            const getCellColor = (): string => {
                                if (!cooldown) return 'border-gray-200';
                                const progress = cooldown / cooldownSeconds;
                                if (progress > 0.66) return 'border-red-500';
                                if (progress > 0.33) return 'border-orange-500';
                                return 'border-yellow-500';
                            };

                            return (
                                <Form
                                    key={position}
                                    action={gridUpdate(position)}
                                    method="put"
                                    onError={(errors: Record<string, string | string[]>) => {
                                        console.error('Validation errors:', errors);
                                    }}
                                >
                                    {({ processing, submit }) => (
                                        <>
                                            <input type="hidden" name="emoji" value={selectedEmoji} />

                                            <button
                                                type="button"
                                                onClick={() => {
                                                    if (!isDisabled) {
                                                        setCells((prev) => ({
                                                            ...prev,
                                                            [position]: selectedEmoji,
                                                        }));
                                                        setTimestamps((prev) => ({
                                                            ...prev,
                                                            [position]: Math.floor(Date.now() / 1000),
                                                        }));
                                                        submit();
                                                    }
                                                }}
                                                disabled={processing || isDisabled}
                                                className={`flex h-full w-full items-center justify-center border-2 transition-all duration-100 ${getCellColor()} bg-white text-4xl hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700`}
                                            >
                                                <div className="relative flex h-full w-full items-center justify-center">
                                                    {cells[position] || ''}
                                                    {cooldown && (
                                                        <div className="absolute right-0 bottom-0 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                            {cooldown}
                                                        </div>
                                                    )}
                                                </div>
                                            </button>
                                        </>
                                    )}
                                </Form>
                            );
                        })}
                    </div>
                </div>
            </div>
        </>
    );
}
