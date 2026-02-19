'use server';
/**
 * @fileOverview A plant health assistant AI agent.
 *
 * - plantHealthAssistant - A function that analyzes plant health from an image and provides conversational advice.
 * - PlantHealthAssistantInput - The input type for the plantHealthAssistant function.
 * - PlantHealthAssistantOutput - The return type for the plantHealthAssistant function.
 */

import {ai} from '@/ai/genkit';
import {z} from 'genkit';

// Define the input schema for the plant health assistant flow
const PlantHealthAssistantInputSchema = z.object({
  photoDataUri: z
    .string()
    .describe(
      "A photo of a plant, as a data URI that must include a MIME type and use Base64 encoding. Expected format: 'data:<mimetype>;base64,<encoded_data>'."
    ),
  conversationHistory: z
    .array(
      z.object({
        role: z.enum(['user', 'model']),
        content: z.string(),
      })
    )
    .optional()
    .describe('Previous turns in the conversation to maintain context.'),
});
export type PlantHealthAssistantInput = z.infer<typeof PlantHealthAssistantInputSchema>;

// Define the output schema for the plant health assistant flow
const PlantHealthAssistantOutputSchema = z.object({
  analysis: z.string().describe('A detailed analysis of the plant\'s health based on the image.'),
  advice: z.string().describe('Conversational advice to the user on how to address any detected issues.'),
  isHealthy: z.boolean().describe('Whether the plant appears healthy based on the analysis.'),
  issuesDetected: z.array(z.string()).describe('A list of specific issues or diseases detected in the plant.'),
});
export type PlantHealthAssistantOutput = z.infer<typeof PlantHealthAssistantOutputSchema>;

export async function plantHealthAssistant(
  input: PlantHealthAssistantInput
): Promise<PlantHealthAssistantOutput> {
  return plantHealthAssistantFlow(input);
}

const plantHealthAssistantPrompt = ai.definePrompt({
  name: 'plantHealthAssistantPrompt',
  input: {schema: PlantHealthAssistantInputSchema},
  output: {schema: PlantHealthAssistantOutputSchema},
  prompt: `You are an expert botanist and plant health doctor, specialized in diagnosing plant issues from visual cues and providing practical, conversational advice to farmers.

Analyze the plant shown in the image and the provided conversation history to give a comprehensive health assessment and tailored advice.

If the plant appears healthy, confirm its health and offer general care tips.
If issues are detected, clearly identify them, explain potential causes, and suggest actionable solutions.
Maintain a friendly, encouraging, and helpful tone, continuing the conversation naturally based on the history.

Provide your response in JSON format according to the output schema.

Conversation History:
{{#if conversationHistory}}
  {{#each conversationHistory}}
    {{role}}: {{{content}}}
  {{/each}}
{{else}}
  No prior conversation. This is the first interaction.
{{/if}}

User: Please analyze the health of this plant and provide advice.
Photo: {{media url=photoDataUri}}`,
});

const plantHealthAssistantFlow = ai.defineFlow(
  {
    name: 'plantHealthAssistantFlow',
    inputSchema: PlantHealthAssistantInputSchema,
    outputSchema: PlantHealthAssistantOutputSchema,
  },
  async (input) => {
    // Genkit's ai.generate will take prompt and history and return a structured response.
    // The prompt already handles the structured input and output.
    const {output} = await plantHealthAssistantPrompt(input);
    if (!output) {
      throw new Error('Failed to get a response from the plant health assistant model.');
    }
    return output;
  }
);
