<?php
namespace controllers;

use Config;
use models\Post;
use util\SimpleBars;

class Archive {
    // Overview with statistics
    public static function showOverview($request, $response) {
        $response->session('backurl', $request->uri());
        $response->title .= Config::PAGE_TITLE_S . 'Archiv';

        $statistics = Post::getYearStatistics(!$response->loggedin);

        // Fill up to 3*n matrix
        $start = $statistics['first'] - 2 + (($statistics['last'] - $statistics['first']) % 3);
        $response->graphs = array();
        for ($year = $start; $year <= $statistics['last']; $year++) {
            if (array_key_exists($year, $statistics['data'])) {
                $data = $statistics['data'][$year];
            } else {
                $data = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0);
            }

            $graph = new SimpleBars();
            $graph->setData($data)
                ->setTitle($year)
                ->setBarWidth(15)
                ->setBarMargin(5)
                ->setGraphHeight(100)
                ->setMaxValue($statistics['max']);

            $response->graphs[$year] = $graph->render();
        }

        $response->postcount = Post::getPostCount(!$response->loggedin);

        $response->render('tpl/statistics.html');
    }

    // Archive
    public static function showYear($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findByYear($request->param('year'), !$response->loggedin);
        $response->title .= Config::PAGE_TITLE_S . 'Archiv (' . $request->param('year') . ')';
        $response->year = $request->param('year');

        if (count($response->posts) == 0) {
            $response->code(404);
        }

        $response->render('tpl/archive.html');
    }
}
