<?php

namespace App\Http\Controllers;

use App\Check\AiAnalysis;
use App\Check\AiAnalysisFailed;
use App\Check\HeuristicAnalysis;
use App\Check\HeuristicAssessment;
use App\Check\LearningCard;
use App\Check\SuspectEmail;
use App\Http\Requests\CheckRequest;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CheckController extends Controller
{
    /**
     * Run a check: the heuristic analysis always, the AI analysis when an
     * API key is configured. Nothing about the suspect email is stored
     * (ADR-0002), so the learning card is rendered directly.
     */
    public function __invoke(CheckRequest $request, HeuristicAnalysis $heuristics, AiAnalysis $ai): Response
    {
        $email = $request->suspectEmail();
        $assessment = $heuristics->analyse($email);

        return Inertia::render('Home', [
            'learningCard' => $this->learningCard($email, $assessment, $ai)->toArray(),
        ]);
    }

    /**
     * Without an API key, or when the AI analysis fails, the check runs in
     * heuristics-only mode. A failure is logged with its reason and cause,
     * never with the suspect email.
     */
    private function learningCard(SuspectEmail $email, HeuristicAssessment $assessment, AiAnalysis $ai): LearningCard
    {
        if (! AiAnalysis::isConfigured()) {
            return LearningCard::fromHeuristics($assessment);
        }

        try {
            return LearningCard::fromHeuristicsAndAi($assessment, $ai->assess($email, $assessment));
        } catch (AiAnalysisFailed $failure) {
            Log::warning('AI analysis failed', $failure->logContext());

            return LearningCard::fromHeuristics($assessment);
        }
    }
}
