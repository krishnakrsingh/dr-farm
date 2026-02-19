export type LiveConfig = {
    model?: string;
    generationConfig?: {
        responseModalities?: string[];
        speechConfig?: {
            voiceConfig?: {
                prebuiltVoiceConfig?: {
                    voiceName: "Puck" | "Charon" | "Kore" | "Fenrir" | "Aoede";
                };
            };
        };
    };
    systemInstruction?: {
        parts: {
            text: string;
        }[];
    };
    tools?: Array<
        | { googleSearch: {} }
        | { codeExecution: {} }
        | { functionDeclarations: unknown[] }
    >;
};

export class LiveClient extends EventTarget {
    public url: string;
    public apiKey?: string;
    public accessToken?: string;
    public ws: WebSocket | null = null;
    protected config: LiveConfig | null = null;

    constructor(url: string, options?: { apiKey?: string; accessToken?: string }) {
        super();
        this.url = url;
        this.apiKey = options?.apiKey;
        this.accessToken = options?.accessToken;
    }

    connect(config: LiveConfig): Promise<void> {
        this.config = config;
        return new Promise((resolve, reject) => {
            let wsURL = this.url;

            // Add authentication query parameters
            if (this.accessToken) {
                wsURL += (wsURL.includes("?") ? "&" : "?") + `access_token=${this.accessToken}`;
            } else if (this.apiKey) {
                wsURL += (wsURL.includes("?") ? "&" : "?") + `key=${this.apiKey}`;
            }

            // Debug logging — helps diagnose connection issues
            const keyParam = this.apiKey || this.accessToken || "";
            console.log("[LiveClient] Connecting to:", wsURL.replace(keyParam, `<token:${keyParam.length}chars>`));
            console.log("[LiveClient] Auth token length:", keyParam.length);

            try {
                this.ws = new WebSocket(wsURL);
            } catch (err) {
                reject(new Error(`Failed to create WebSocket: ${err}`));
                return;
            }

            this.ws.addEventListener("open", () => {
                console.log(`Connected to WebSocket`);
                this.sendSetup(config);
                this.dispatchEvent(new CustomEvent("open"));
                resolve();
            });

            this.ws.addEventListener("message", this.receive.bind(this));

            this.ws.addEventListener("close", (ev) => {
                console.log(`WebSocket closed: code=${ev.code} reason=${ev.reason}`);
                this.dispatchEvent(new CustomEvent("close", { detail: ev }));
            });

            this.ws.addEventListener("error", (ev) => {
                console.error("WebSocket error:", ev);
                this.dispatchEvent(new CustomEvent("error", { detail: ev }));
                reject(new Error("WebSocket connection failed"));
            });
        });
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }

    protected sendSetup(config: LiveConfig) {
        if (!this.ws) {
            throw new Error("WebSocket is not connected");
        }
        const setupMessage = {
            setup: config,
        };
        console.log("[LiveClient] Sending setup:", JSON.stringify(setupMessage, null, 2));
        this.ws.send(JSON.stringify(setupMessage));
    }

    send(
        parts: Record<string, unknown> | Record<string, unknown>[],
        turnComplete = false
    ) {
        if (!this.ws) {
            throw new Error("WebSocket is not connected");
        }

        const message = {
            client_content: {
                turns: [
                    {
                        role: "user",
                        parts: Array.isArray(parts) ? parts : [parts],
                    },
                ],
                turn_complete: turnComplete,
            },
        };
        this.ws.send(JSON.stringify(message));
    }

    /**
     * Send continuous real-time input (audio/video chunks).
     */
    sendRealtimeInput(chunks: { mimeType: string; data: string }[]) {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
            return; // Silently skip if not connected
        }
        const message = {
            realtime_input: {
                media_chunks: chunks,
            },
        };
        this.ws.send(JSON.stringify(message));
    }

    protected receive(event: MessageEvent) {
        if (event.data instanceof Blob) {
            event.data.text().then((text) => this.parseResponse(text));
        } else {
            this.parseResponse(event.data as string);
        }
    }

    protected parseResponse(text: string) {
        try {
            const data = JSON.parse(text);
            console.log("[LiveClient] Server message:", JSON.stringify(data).substring(0, 500));
            this.dispatchEvent(new CustomEvent("message", { detail: data }));

            // Convenience events
            if (data.serverContent) {
                const content = data.serverContent;
                if (content.modelTurn) {
                    this.dispatchEvent(
                        new CustomEvent("content", {
                            detail: content.modelTurn,
                        })
                    );
                    // Check for audio parts
                    if (content.modelTurn.parts) {
                        for (const part of content.modelTurn.parts) {
                            if (
                                part.inlineData &&
                                part.inlineData.mimeType?.startsWith("audio/")
                            ) {
                                this.dispatchEvent(
                                    new CustomEvent("audio", {
                                        detail: part.inlineData,
                                    })
                                );
                            }
                        }
                    }
                }
                if (content.turnComplete) {
                    this.dispatchEvent(new CustomEvent("turnComplete"));
                }
            }

            // Handle setup complete acknowledgment
            if (data.setupComplete) {
                this.dispatchEvent(new CustomEvent("setupComplete"));
            }
        } catch (e) {
            console.error("Error parsing WebSocket response:", e, "Raw:", text.substring(0, 200));
        }
    }
}
