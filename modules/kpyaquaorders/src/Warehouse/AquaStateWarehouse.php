<?php

namespace PrestaShop\Module\KpyAquaOrders\Warehouse;

class AquaStateWarehouse
{
    private const string STATES_FILE = __DIR__ . '/aqua_states.json';

    private array $communitiesByState = [];
    
    public function __construct()
    {
        if (is_readable(self::STATES_FILE)) {
            $data = json_decode(file_get_contents(self::STATES_FILE), true);

            foreach ($data['6'] as $community => $states) {
                foreach ($states as $state) {
                    $this->communitiesByState[$state] = $community;
                }
            }
        }
    }

    public function findCommunityByState(int $stateId): string
    {
        return $this->communitiesByState[$stateId] ?? '';
    }

}