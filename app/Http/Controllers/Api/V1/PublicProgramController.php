<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProgramStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProgramResource;
use App\Models\Program;

class PublicProgramController extends Controller
{
    public function index()
    {
        $programs = Program::where('status', ProgramStatus::Active)
            ->orderBy('order')->orderByDesc('id')->get();

        return ProgramResource::collection($programs);
    }

    public function show(Program $program)
    {
        abort_if($program->status !== ProgramStatus::Active, 404);

        return new ProgramResource($program);
    }
}
