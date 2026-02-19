"use client";

import { useEffect, useRef, useState, useCallback } from "react";
import { useLiveApi, ConnectOptions } from "@/hooks/use-live-api";
import { Button } from "@/components/ui/button";
import { Mic, Square, Loader2, AlertCircle } from "lucide-react";
import { cn } from "@/lib/utils";

type AuthInfo = {
    ephemeralToken?: string;
    authMethod?: "ephemeral-token";
    error?: string;
};

/**
 * Build connect options using the ephemeral token.
 * The official @google/genai SDK handles URL construction internally.
 * We only need to pass the ephemeral token as accessToken.
 */
function buildConnectOptions(auth: AuthInfo): ConnectOptions {
    return {
        url: "", // SDK handles URL construction
        accessToken: auth.ephemeralToken,
    };
}

export default function LiveAnalysis() {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [videoStream, setVideoStream] = useState<MediaStream | null>(null);
    const [authInfo, setAuthInfo] = useState<AuthInfo | null>(null);
    const [authError, setAuthError] = useState<string | null>(null);
    const [isStreaming, setIsStreaming] = useState(false);

    const { connect, disconnect, connected, volume } = useLiveApi();

    /**
     * Fetch a fresh ephemeral token from the backend.
     * Always fetches a NEW token before each session (tokens are single-use).
     */
    const fetchToken = useCallback(async (): Promise<AuthInfo | null> => {
        try {
            setAuthError(null);
            const res = await fetch("/api/vertex-auth");

            // Guard: if the response isn't JSON (e.g. HTML error page), don't try to parse it
            const contentType = res.headers.get("content-type") || "";
            if (!contentType.includes("application/json")) {
                const text = await res.text();
                console.error("Auth endpoint returned non-JSON:", res.status, text.substring(0, 200));
                setAuthError(
                    "Auth service returned an unexpected response. Check server logs."
                );
                return null;
            }

            const data = await res.json();

            if (!res.ok || data.error) {
                const errMsg = data.error || `Auth request failed (${res.status})`;
                console.error("Auth error:", errMsg);
                setAuthError(errMsg);
                return null;
            }

            const info: AuthInfo = {
                ephemeralToken: data.ephemeralToken,
                authMethod: data.authMethod,
            };
            setAuthInfo(info);
            return info;
        } catch (err) {
            const message = err instanceof Error ? err.message : "Network error fetching auth";
            console.error("fetchToken error:", message);
            setAuthError(
                "Could not reach auth service. Check that the dev server is running."
            );
            return null;
        }
    }, []);

    // Fetch initial token on mount (just to check the API key is valid)
    useEffect(() => {
        fetchToken();
    }, [fetchToken]);

    // Camera initialization
    useEffect(() => {
        let stream: MediaStream | null = null;
        let cancelled = false;

        const startCamera = async () => {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setAuthError("Camera access not supported. Ensure you are using HTTPS or localhost.");
                return;
            }
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: "environment" }, // falls back to front camera on desktop
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                    },
                    audio: {
                        sampleRate: 16000,
                        channelCount: 1,
                    },
                });

                if (cancelled) {
                    stream.getTracks().forEach((t) => t.stop());
                    return;
                }

                setVideoStream(stream);
                if (videoRef.current) {
                    videoRef.current.srcObject = stream;
                    // Force play after attaching stream to avoid green frame
                    try {
                        await videoRef.current.play();
                    } catch {
                        // Will be handled by onLoadedMetadata
                    }
                }
            } catch (err: unknown) {
                console.error("Error accessing camera:", err);
                if (!cancelled) {
                    const error = err as DOMException;
                    if (error.name === "NotReadableError") {
                        setAuthError(
                            "Camera is in use by another app. Close other apps using the camera (Zoom, Teams, etc.), then refresh this page."
                        );
                    } else if (error.name === "NotAllowedError") {
                        setAuthError(
                            "Camera permission denied. Please allow camera access in your browser settings and refresh."
                        );
                    } else if (error.name === "NotFoundError") {
                        setAuthError(
                            "No camera detected. Please connect a camera and refresh."
                        );
                    } else {
                        setAuthError(
                            `Camera error (${error.name || "unknown"}): ${error.message || "Could not access camera."}`
                        );
                    }
                }
            }
        };
        startCamera();

        return () => {
            cancelled = true;
            if (stream) {
                stream.getTracks().forEach((t) => t.stop());
            }
        };
    }, []);

    // Handle video play safely to avoid "play() interrupted" errors
    const handleVideoReady = useCallback(() => {
        const video = videoRef.current;
        if (video && video.paused) {
            video.play().catch((e) => {
                // AbortError is expected when a new load interrupts play
                if (e.name !== "AbortError") {
                    console.error("Video play error:", e);
                }
            });
        }
    }, []);

    const toggleLive = async () => {
        if (isStreaming) {
            disconnect();
            setIsStreaming(false);
        } else {
            // ALWAYS get a FRESH ephemeral token for each session (single-use tokens)
            const currentAuth = await fetchToken();
            if (!currentAuth || !currentAuth.ephemeralToken) {
                return; // authError is already set
            }

            setIsStreaming(true);

            try {
                // Build connect options using the ephemeral token
                const connectOpts = buildConnectOptions(currentAuth);

                const config: Parameters<typeof connect>[0] = {
                    model: "models/gemini-2.5-flash-native-audio-latest",
                    generationConfig: {
                        responseModalities: ["AUDIO"],
                        speechConfig: {
                            voiceConfig: {
                                prebuiltVoiceConfig: {
                                    voiceName: "Aoede",
                                },
                            },
                        },
                    },
                    systemInstruction: {
                        parts: [
                            {
                                text: "You are an expert plant doctor. Analyze the video stream to identify plants and their health issues. Be concise, friendly, and helpful to the farmer. If you see a plant, describe its condition and suggest solutions for any issues.",
                            },
                        ],
                    },
                };

                await connect(config, videoRef.current, connectOpts);
            } catch (err) {
                console.error("Connection error:", err);
                setIsStreaming(false);
                setAuthError("Failed to connect to the AI service. Please try again.");
            }
        }
    };

    // Auto-stop if connection drops
    const wasConnected = useRef(false);
    useEffect(() => {
        if (connected) {
            wasConnected.current = true;
        } else if (wasConnected.current && isStreaming) {
            setIsStreaming(false);
            wasConnected.current = false;
            setAuthError("Connection closed by server.");
        }
    }, [connected, isStreaming]);

    return (
        <div className="relative w-full h-[500px] md:h-[600px] rounded-3xl overflow-hidden bg-black shadow-2xl">
            {/* 
              Green screen fix: use CSS transform: translateZ(0) to force GPU compositing 
              and avoid the green frame issue that occurs with certain hardware acceleration + video elements.
              Also set background to transparent and use object-fit cover.
            */}
            <video
                ref={videoRef}
                autoPlay
                playsInline
                muted
                onLoadedMetadata={handleVideoReady}
                className="absolute inset-0 w-full h-full object-cover"
                style={{
                    transform: "translateZ(0)",
                    WebkitTransform: "translateZ(0)",
                    backfaceVisibility: "hidden",
                    WebkitBackfaceVisibility: "hidden",
                    background: "transparent",
                }}
            />

            {/* Overlay UI */}
            <div className="absolute inset-0 pointer-events-none flex flex-col justify-between p-6">
                <div className="flex justify-between items-start">
                    <div
                        className={cn(
                            "px-4 py-2 rounded-full backdrop-blur-md bg-black/30 text-white border border-white/10 flex items-center gap-2",
                            connected
                                ? "text-green-400 border-green-500/50"
                                : "text-white/70"
                        )}
                    >
                        <div
                            className={cn(
                                "w-2 h-2 rounded-full",
                                connected
                                    ? "bg-green-500 animate-pulse"
                                    : "bg-white/50"
                            )}
                        />
                        {connected ? "Live Analysis Active" : "Ready"}
                    </div>

                    {authInfo?.authMethod && (
                        <div className="px-3 py-1.5 rounded-full backdrop-blur-md bg-black/30 text-white/60 border border-white/10 text-xs">
                            Gemini Live
                        </div>
                    )}
                </div>

                {/* Auth Error Banner */}
                {authError && !isStreaming && (
                    <div className="pointer-events-auto mx-auto max-w-md">
                        <div className="bg-red-500/90 backdrop-blur-md text-white px-4 py-3 rounded-2xl text-sm flex items-start gap-2">
                            <AlertCircle size={18} className="shrink-0 mt-0.5" />
                            <span>{authError}</span>
                        </div>
                    </div>
                )}

                <div className="pointer-events-auto flex items-center justify-center gap-6">
                    {/* Volume Visualizer if connected */}
                    {connected && (
                        <div className="h-16 flex items-center gap-1">
                            {[...Array(5)].map((_, i) => (
                                <div
                                    key={i}
                                    className="w-2 bg-green-500 rounded-full transition-all duration-75"
                                    style={{
                                        height: `${Math.max(
                                            10,
                                            Math.min(
                                                60,
                                                volume * 500 * (i + 1)
                                            )
                                        )}px`,
                                        opacity: 0.8,
                                    }}
                                />
                            ))}
                        </div>
                    )}

                    <Button
                        onClick={toggleLive}
                        size="lg"
                        className={cn(
                            "rounded-full h-20 w-20 flex items-center justify-center transition-all duration-300 shadow-xl border-4",
                            isStreaming
                                ? "bg-red-500 hover:bg-red-600 border-red-200 text-white"
                                : "bg-blue-600 hover:bg-blue-700 border-blue-200 text-white"
                        )}
                    >
                        {isStreaming ? (
                            connected ? (
                                <Square size={32} fill="currentColor" />
                            ) : (
                                <Loader2
                                    size={32}
                                    className="animate-spin"
                                />
                            )
                        ) : (
                            <Mic size={32} />
                        )}
                    </Button>
                </div>
            </div>

            {!videoStream && !authError && (
                <div className="absolute inset-0 flex items-center justify-center bg-gray-900 text-white z-10">
                    <Loader2 className="animate-spin mr-2" /> Initializing
                    Camera...
                </div>
            )}
        </div>
    );
}
