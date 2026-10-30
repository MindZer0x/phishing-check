<?php

namespace App\Http\Controllers;

use App\Check\HeuristicAnalysis;
use App\Check\LearningCard;
use App\Http\Requests\CheckRequest;
use Inertia\Inertia;
use Inertia\Response;

class CheckController extends Controller
{
    /**
     * Run a check in heuristics-only mode. Nothing about the suspect email
     * is stored (ADR-0002), so the learning card is rendered directly.
     */
    public function __invoke(CheckRequest $request, HeuristicAnalysis $analysis): Response
    {
        $assessment = $analysis->analyse($request->suspectEmail());

        return Inertia::render('Home', [
            'learningCard' => LearningCard::fromHeuristics($assessment)->toArray(),
        ]);
    }
}
