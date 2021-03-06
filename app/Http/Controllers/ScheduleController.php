<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Services\Locations;
use App\Services\Maps;
use App\Services\Schedules;
use App\Services\Seasons;
use App\Services\Villagers;
use Illuminate\Support\Collection;

class ScheduleController extends Controller
{
    public function mainPage(Villagers $villagers, Seasons $seasons)
    {
        return view('index')
            ->with('villagers', $villagers->getList()->sort())
            ->with('seasons', $seasons->getList())
            ->with('days', new Collection(range(1, 28)));
    }

    public function getSchedule(ScheduleRequest $request, Locations $locations)
    {
        $schedules = new Schedules($request->input('villager'));
        $schedule = $schedules->getFor($request->input('season'), $request->input('day'));
        $locations->parseLocations($request->input('villager'), $schedule['schedules']);

        return response()->json($schedule);
    }

    public function map($name, $x, $y, Maps $maps)
    {
        $map = $maps->getMap($name, $x, $y);
        if (!$map) {
            abort(404);
        }

        return $map->response();
    }

    public function fullMap($name, Maps $maps)
    {
        $map = $maps->getFullMap($name);
        if (!$map) {
            abort(404);
        }

        return $map->response();
    }

    public function mapSizes(Maps $maps)
    {
        return json_encode($maps->mapSizes());
    }

    public function portrait($name, Villagers $villagers)
    {
        $portrait = $villagers->getPortrait($name);
        if (!$portrait) {
            abort(404);
        }

        return $portrait->response();
    }
}
