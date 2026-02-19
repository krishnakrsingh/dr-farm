export class AudioStreamer {
    public audioContext: AudioContext | null = null;
    public gainNode: GainNode | null = null;
    public audioQueue: Float32Array[] = [];
    public isPlaying = false;
    public startTime = 0;

    constructor() {
        // Don't create AudioContext here — browsers require user interaction first
    }

    private ensureContext(): AudioContext {
        if (!this.audioContext || this.audioContext.state === "closed") {
            this.audioContext = new (window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext)({
                sampleRate: 24000, // Gemini Live output is 24kHz
            });
            this.gainNode = this.audioContext.createGain();
            this.gainNode.connect(this.audioContext.destination);
        }
        return this.audioContext;
    }

    addPCM16(chunk: Int16Array) {
        const float32 = new Float32Array(chunk.length);
        for (let i = 0; i < chunk.length; i++) {
            float32[i] = chunk[i] / 32768;
        }
        this.audioQueue.push(float32);
        if (!this.isPlaying) {
            this.playQueue();
        }
    }

    async playQueue() {
        this.isPlaying = true;
        const ctx = this.ensureContext();

        if (ctx.state === "suspended") {
            await ctx.resume();
        }

        let nextTime = this.startTime;
        if (nextTime < ctx.currentTime) {
            nextTime = ctx.currentTime;
        }

        while (this.audioQueue.length > 0) {
            const buffer = this.audioQueue.shift();
            if (!buffer) break;

            const audioBuffer = ctx.createBuffer(1, buffer.length, 24000);
            audioBuffer.getChannelData(0).set(buffer);

            const source = ctx.createBufferSource();
            source.buffer = audioBuffer;
            source.connect(this.gainNode!);
            source.start(nextTime);
            nextTime += audioBuffer.duration;
        }

        this.startTime = nextTime;

        if (nextTime > ctx.currentTime) {
            setTimeout(() => {
                if (this.audioQueue.length > 0) this.playQueue();
                else this.isPlaying = false;
            }, (nextTime - ctx.currentTime) * 1000);
        } else {
            this.isPlaying = false;
        }
    }

    async resume() {
        const ctx = this.ensureContext();
        if (ctx.state === "suspended") {
            await ctx.resume();
        }
    }

    stop() {
        this.isPlaying = false;
        this.audioQueue = [];
        this.startTime = 0;
        if (this.audioContext && this.audioContext.state !== "closed") {
            this.audioContext.close().catch(() => {
                // Ignore close errors
            });
        }
        this.audioContext = null;
        this.gainNode = null;
    }
}
