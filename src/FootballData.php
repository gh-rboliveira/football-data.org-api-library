<?php

namespace gh_rboliveira\football_data;

use stdClass;

/**
 * A Class to communicate with football-data.org
 */
class FootballData
{

    /**
     * Authorization Token from football-data.org
     *
     * @var string
     */
    private $auth_token;

    /**
     * football-data.org API base uri - defaulted to v2
     *
     * @var string
     */
    private $base_uri = 'http://api.football-data.org/v2/';

    /**
     * Initiate and validate for empty tokens
     */
    public function __construct(string $auth_token)
    {
        if (empty($auth_token)) {
            throw new \InvalidArgumentException('Missing configuration for auth token!');
        }
        $this->auth_token = $auth_token;
    }

    /**
     * Make a curl call to our endpoint
     *
     * @param string $resource - resource url
     * @return stdClass - Json with the API answer
     */
    private function call_api(string $resource): stdClass
    {

        $url = $this->base_uri . $resource;
        $headers = array(
            'X-Auth-Token: ' . $this->auth_token,
            "Content-Type: application/json",
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        curl_close($ch);

        $decoded_response = json_decode($result);

        if (isset($decoded_response->errorCode)) {
            throw new \Exception(
                "Problem football-data.org API [" .
                    $this->base_uri . $resource . "] " . $decoded_response->message,
                $decoded_response->errorCode
            );
        }


        return $decoded_response;
    }

    /**
     * Auxiliar method to create query string.
     * it will append the corresponding $key=$value
     *
     * @param string $current_query 
     * @param string $key
     * @param string $value 
     * @param callable|null $validator - if applicable do $value validation
     * @return string
     */
    private function append_query(string $current_query, string $key, string $value, callable $validator = null): string
    {
        if ($value == "") return $current_query;

        if (!is_null($validator) && !call_user_func($validator, $value)) throw new \InvalidArgumentException("Invalid value for " . $key . "[" . $value . "]");

        if (substr($current_query, -1) != "?") {
            $current_query .= "&";
        }
        $current_query .= $key . "=" . $value;
        return $current_query;
    }

    /**
     * VALIDATORS
     */

    /**
     * Validates areas argument
     * Validates competitions arguments
     *
     * Comma separated integers (corresponding to areas ids)
     *
     * @param string $areas
     * @return boolean - valid or not
     */
    private function validate_string_ints(string $areas): bool
    {
        $areas = explode(",", $areas);
        foreach ($areas as $area) {
            if (!intval($area)) return false;
        }
        return true;
    }

    /**
     * Validates plan argument
     *
     * String /[A-Z]+/	[ TIER_ONE | TIER_TWO | TIER_THREE | TIER_FOUR ]
     * 
     * @param string $plan
     * @return boolean - valid or not
     */
    private function validate_plan(string $plan): bool
    {
        $valid_plans = array('TIER_ONE', 'TIER_TWO', 'TIER_THREE', 'TIER_FOUR');
        return in_array($plan, $valid_plans);
    }

    /**
     * Validates that season is a year
     * 
     * String /YYYY/
     *
     * @param string $season
     * @return boolean - valid or not
     */
    private function validate_season(string $season): bool
    {
        $year = (int)$season;
        return ($year > 1111 && $year < 2100);
    }

    /**
     * Validates the standing argument
     * 
     * String /[A-Z]+/	[ TOTAL (default) | HOME | AWAY ]
     *
     * @param string $standing_type
     * @return boolean - valid or not
     */
    private function validate_standing_type(string $standing_type): bool
    {
        $valid_plans = array('TOTAL', 'HOME', 'AWAY');
        return in_array($standing_type, $valid_plans);
    }

    /**
     * Validates date format YYYY-MM-DD
     *
     * String /YYYY-MM-dd/	e.g. 2018-06-22
     * 
     * @param string $date
     * @return boolean - valid or not
     */
    private function validate_date(string $date): bool
    {
        if (!preg_match("/^(20[0-9]{2})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) return false;

        return true;
    }

    /**
     * Validates the status argument
     * 
     * Enum /[A-Z]+/	[SCHEDULED | LIVE | IN_PLAY | PAUSED | FINISHED | POSTPONED | SUSPENDED | CANCELED]
     *
     * @param string $status
     * @return boolean - valid or not
     */
    private function validate_status(string $status)
    {
        $valid_status = array('SCHEDULED', 'LIVE', 'IN_PLAY', 'PAUSED', 'FINISHED', 'POSTPONED', 'SUSPENDED', 'CANCELED');
        return in_array($status, $valid_status);
    }

    /**
     * Validates the venue argument
     * 
     * 	Enum /[A-Z]+/ [HOME | AWAY]
     *
     * @param string $venue
     * @return void - valid or not
     */
    private function validate_venue(string $venue)
    {
        $valid_venues = array('HOME', 'AWAY');
        return in_array($venue, $valid_venues);
    }


    /**
     * List all available competitions.
     * 
     * /v2/competitions/
     *
     * @param array $areas [Optional] - Array of Integers (IDs of Areas) to filter
     * @param string $plan [Optional] - User Tier plan 
     * @return stdClass
     */
    public function get_available_competitions(array $areas = [], string $plan = ""): stdClass
    {
        $resource = "competitions/?";

        $resource = $this->append_query($resource, "areas", implode(",", $areas), [$this, 'validate_string_ints']);
        $resource = $this->append_query($resource, "plan", $plan, [$this, 'validate_plan']);

        return $this->call_api($resource);
    }

    /**
     * List one particular competition.
     * 
     * /v2/competitions/{$competition_id}
     *
     * @param integer $competition_id
     * @return stdClass
     */
    public function get_competition(int $competition_id): stdClass
    {
        $resource = "competitions/" . $competition_id;

        return $this->call_api($resource);
    }

    /**
     * List all teams for a particular competition.
     * 
     * /v2/competitions/{$competition_id}/teams
     *
     * @param integer $competition_id
     * @param string $season
     * @param string $stage
     * @return stdClass
     */
    public function get_competition_teams(int $competition_id, string $season = "", string $stage = ""): stdClass
    {
        $resource = "competitions/" . $competition_id . "/teams?";

        $resource = $this->append_query($resource, "season", $season, [$this, 'validate_season']);
        $resource = $this->append_query($resource, "stage", $stage);

        return $this->call_api($resource);
    }

    /**
     * Show Standings for a particular competition.
     * 
     * /v2/competitions/{$competition_id}/standings	
     *
     * @param integer $competition_id
     * @param string $standing_type
     * @return stdClass
     */
    public function get_competition_standings(int $competition_id, string $standing_type = ""): stdClass
    {
        $resource = "competitions/" . $competition_id . "/standings?";

        $resource = $this->append_query($resource, "standingType", $standing_type, [$this, 'validate_standing_type']);

        return $this->call_api($resource);
    }

    /**
     * List all matches for a particular competition.
     * 
     * /v2/competitions/{$competition_id}/matches
     *
     * @param integer $competition_id
     * @param string $date_from
     * @param string $date_to
     * @param string $stage
     * @param string $status
     * @param integer|null $matchday
     * @param string $group
     * @param string $season
     * @return stdClass
     */
    public function get_competition_matches(
        int $competition_id,
        string $date_from = "",
        string $date_to = "",
        string $stage = "",
        string $status = "",
        int $matchday = null,
        string $group = "",
        string $season = ""
    ): stdClass {
        $resource = "competitions/" . $competition_id . "/matches?";

        $resource = $this->append_query($resource, "dateFrom", $date_from, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "dateTo", $date_to, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "stage", $stage);
        $resource = $this->append_query($resource, "status", $status, [$this, 'validate_status']);
        $resource = $this->append_query($resource, "matchday", (string)$matchday);
        $resource = $this->append_query($resource, "group", $group);
        $resource = $this->append_query($resource, "season", $season, [$this, 'validate_season']);

        return $this->call_api($resource);
    }

    /**
     * List goal scorers for a particular competition.
     * 
     * /v2/competitions/{$competition_id}/scorers
     *
     * @param integer $competition_id
     * @param integer $limit
     * @return stdClass
     */
    public function get_competition_scorers(int $competition_id, int $limit = 10): stdClass
    {
        $resource = "competitions/" . $competition_id . "/scorers?";
        $resource = $this->append_query($resource, "limit", $limit);

        return $this->call_api($resource);
    }

    /**
     * List matches across (a set of) competitions.
     * 
     * /v2/matches	
     *
     * @param array $competitions
     * @param string $date_from
     * @param string $date_to
     * @param string $status
     * @return stdClass
     */
    public function get_matches(
        array $competitions = [],
        string $date_from = "",
        string $date_to = "",
        string $status = ""
    ): stdClass {
        $resource = "matches?";
        $resource = $this->append_query($resource, "competitions", implode(",", $competitions), [$this, 'validate_string_ints']);
        $resource = $this->append_query($resource, "dateFrom", $date_from, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "dateTo", $date_to, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "status", $status, [$this, 'validate_status']);

        return $this->call_api($resource);
    }

    /**
     * Show one particular match.
     * 
     * /v2/matches/{$match_id}
     *
     * @param integer $match_id
     * @return stdClass
     */
    public function get_match(int $match_id): stdClass
    {
        $resource = "matches/" . $match_id;

        return $this->call_api($resource);
    }

    /**
     * Show all matches for a particular team.
     * 
     * /v2/teams/{$team_id}/matches/
     *
     * @param integer $team_id
     * @param string $date_from
     * @param string $date_to
     * @param string $status
     * @param string $venue
     * @param integer $limit
     * @return stdClass
     */
    public function get_team_matches(
        int $team_id,
        string $date_from = "",
        string $date_to = "",
        string $status = "",
        string $venue = "",
        int $limit = 10
    ): stdClass {
        $resource = "/teams/" . $team_id . "/matches/?";

        $$resource = $this->append_query($resource, "dateFrom", $date_from, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "dateTo", $date_to, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "status", $status, [$this, 'validate_status']);
        $resource = $this->append_query($resource, "venue", $venue, [$this, 'validate_venue']);
        $resource = $this->append_query($resource, "limit", $limit);

        return $this->call_api($resource);
    }


    /**
     * Show one particular team.
     * 
     * /v2/teams/{$team_id}
     *
     * @param integer $team_id
     * @return stdClass
     */
    public function get_team(int $team_id): stdClass
    {
        $resource = "teams/" . $team_id;

        return $this->call_api($resource);
    }

    /**
     * List all available areas.
     * 
     * /v2/areas/
     *
     * @return stdClass
     */
    public function get_areas(): stdClass
    {
        $resource = "areas/";

        return $this->call_api($resource);
    }

    /**
     * List one particular area.
     * 
     * /v2/areas/{$area_id}
     *
     * @param integer $area_id
     * @return stdClass
     */
    public function get_area(int $area_id): stdClass
    {
        $resource = "areas/" . $area_id;

        return $this->call_api($resource);
    }

    /**
     * List one particular player.
     * 
     * /v2/players/{$player_id}
     *
     * @param integer $player_id
     * @return stdClass
     */
    public function get_player(int $player_id): stdClass
    {
        $resource = "players/" . $player_id;

        return $this->call_api($resource);
    }

    /**
     * Show all matches for a particular player.
     * 
     * /v2/players/{$player_id}/matches
     *
     * @param integer $player_id
     * @param string $date_from
     * @param string $date_to
     * @param string $status
     * @param array $competitions
     * @param integer $limit
     * @return void
     */
    public function get_player_matches(
        int $player_id,
        string $date_from = "",
        string $date_to = "",
        string $status = "",
        array $competitions = [],
        int $limit = 10
    ) {
        $resource = "players/" . $player_id . "/matches?";
        $resource = $this->append_query($resource, "dateFrom", $date_from, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "dateTo", $date_to, [$this, 'validate_date']);
        $resource = $this->append_query($resource, "status", $status, [$this, 'validate_status']);
        $resource = $this->append_query($resource, "competitions", implode(",", $competitions), [$this, 'validate_string_ints']);
        $resource = $this->append_query($resource, "limit", $limit);

        return $this->call_api($resource);
    }
}