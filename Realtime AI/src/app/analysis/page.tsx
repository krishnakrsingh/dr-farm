
"use client";

import { Info } from "lucide-react";
import LiveAnalysis from "@/components/live-analysis";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function AnalysisPage() {
  return (
    <div className="grid grid-cols-1 gap-8 h-full max-w-4xl mx-auto">
      <div className="space-y-4">
        <h1 className="text-3xl font-bold text-primary flex items-center gap-2">
          AI Plant Doctor (Live)
        </h1>

        {/* Live Analysis Component handles the camera and Gemini Live session */}
        <LiveAnalysis />

        <p className="text-center text-muted-foreground text-sm mt-4">
          Tap the microphone button to start a live consultation with the AI Plant Doctor.
          Point your camera at the plant and describe your issue.
        </p>
      </div>

      <div className="flex flex-col gap-4">
        <Card className="bg-primary/5 border-primary/20">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-primary">
              <Info size={20} />
              How to use
            </CardTitle>
          </CardHeader>
          <CardContent className="text-sm text-muted-foreground space-y-2">
            <p>1. Ensure you have granted camera and microphone permissions.</p>
            <p>2. Click the microphone icon to start.</p>
            <p>3. Show your plant to the camera and ask questions like "Is this plant healthy?" or "What are these spots?".</p>
            <p>4. The AI will respond vividly with voice.</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

