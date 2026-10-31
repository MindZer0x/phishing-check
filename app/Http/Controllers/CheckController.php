<?php

namespace App\Http\Controllers;

use App\Check\AiAnalysis;
use App\Check\HeuristicAnalysis;
use App\Check\LearningCard;
use App\Http\Requests\CheckRequest;
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

        $learningCard = AiAnalysis::isConfigured()
            ? LearningCard::fromHeuristicsAndAi($assessment, $ai->assess($email, $assessment))
            : LearningCard::fromHeuristics($assessment);

        return Inertia::render('Home', [
            'learningCard' => $learningCard->toArray(),
        ]);
    }
}
