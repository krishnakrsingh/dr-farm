import { NextResponse } from "next/server";
import { GoogleGenAI } from "@google/genai";

/**
 * This route provides an ephemeral session token for the Gemini Live API.
 *
 * Flow:
 *  1. Backend uses the long-lived API key to request a short-lived ephemeral token
 *  2. Ephemeral token is sent to the browser client
 *  3. Browser uses the ephemeral token (like an API key) to open the WebSocket
 *
 * This is the correct architecture for client-to-server Live API connections.
 * Raw API keys do NOT work for Live API WebSocket connections from the browser.
 */
export async function GET() {
    try {
        return await tryGetEphemeralToken();
    } catch (outerError) {
        // Catch-all safety net so we ALWAYS return valid JSON
        console.error("Unexpected error in vertex-auth route:", outerError);
        return NextResponse.json(
            { error: "Unexpected server error in auth route." },
            { status: 500 }
        );
    }
}

async function tryGetEphemeralToken() {
    const geminiApiKey = process.env.NEXT_PUBLIC_GEMINI_API_KEY;

    if (!geminiApiKey || geminiApiKey.trim().length === 0) {
        return NextResponse.json(
            {
                error:
                    "No NEXT_PUBLIC_GEMINI_API_KEY configured. " +
                    "Please set it in .env.local to use the Live API.",
            },
            { status: 500 }
        );
    }

    try {
        // Create a server-side GenAI client using the long-lived API key
        const client = new GoogleGenAI({ apiKey: geminiApiKey.trim() });

        // Request an ephemeral token for the Live API
        const expireTime = new Date(Date.now() + 30 * 60 * 1000).toISOString(); // 30 min
        const newSessionExpireTime = new Date(Date.now() + 2 * 60 * 1000); // 2 min to start

        const token = await client.authTokens.create({
            config: {
                uses: 1,
                expireTime: expireTime,
                newSessionExpireTime: newSessionExpireTime,
                httpOptions: { apiVersion: "v1alpha" },
            },
        });

        if (!token || !token.name) {
            console.error("Ephemeral token creation returned empty token:", token);
            return NextResponse.json(
                { error: "Failed to create ephemeral session token." },
                { status: 500 }
            );
        }

        return NextResponse.json({
            ephemeralToken: token.name,
            authMethod: "ephemeral-token",
        });
    } catch (error) {
        const message = (error as Error).message || String(error);
        console.error("Ephemeral token creation failed:", message);

        return NextResponse.json(
            {
                error: `Failed to create ephemeral token: ${message}`,
            },
            { status: 500 }
        );
    }
}
