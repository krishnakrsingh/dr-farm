export class AudioRecorder {
    public audioContext: AudioContext;
    public scriptProcessor: ScriptProcessorNode | null = null;
    public source: MediaStreamAudioSourceNode | null = null;
    public sampleRate = 16000;
    public onData: (buffer: Int16Array) => void;
    public stream: MediaStream | null = null;

    constructor(onData: (buffer: Int16Array) => void) {
        this.onData = onData;
        this.audioContext = new AudioContext({ sampleRate: this.sampleRate });
    }

    async start(stream: MediaStream) {
        this.stream = stream;
        if (this.audioContext.state === "suspended") {
            await this.audioContext.resume();
        }

        // Create source
        this.source = this.audioContext.createMediaStreamSource(stream);

        // Use ScriptProcessor for processing (simple but deprecated)
        // Buffer size: 4096 samples ≈ 250ms at 16k? No, 4096/16000 = 0.256s.
        // Try smaller for lower latency: 1024 or 512.
        // However, ScriptProcessor minimum size constraints exist.
        this.scriptProcessor = this.audioContext.createScriptProcessor(1024, 1, 1);

        this.source.connect(this.scriptProcessor);
        this.scriptProcessor.connect(this.audioContext.destination);

        this.scriptProcessor.onaudioprocess = (e) => {
            const inputBuffer = e.inputBuffer.getChannelData(0); // Float32
            // Convert Float32 to Int16
            const int16Buffer = new Int16Array(inputBuffer.length);
            for (let i = 0; i < inputBuffer.length; i++) {
                let s = Math.max(-1, Math.min(1, inputBuffer[i]));
                int16Buffer[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }
            this.onData(int16Buffer);
        };
    }

    stop() {
        if (this.scriptProcessor) {
            this.scriptProcessor.disconnect();
            this.scriptProcessor.onaudioprocess = null;
            this.scriptProcessor = null;
        }
        if (this.source) {
            this.source.disconnect();
            this.source = null;
        }
        // We don't close context to reuse it, or we can close it.
        // We don't stop stream tracks here, managed by caller.
    }
}
