<?php
/**
 * This file is part of the Gamifications module.
 *
 * @author    Sarunas Jonusas, <jonusas.sarunas@gmail.com>
 * @copyright Copyright (c) permanent, Sarunas Jonusas
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Class AdminGamificationsPreferenceController
 */
class AdminGamificationsPreferenceController extends GamificationsAdminController
{
    public function init()
    {
        $this->initOptions();

        parent::init();
    }

    /**
     * Initalize options
     */
    protected function initOptions()
    {
        if (!empty($this->fields_options)) {
            return;
        }

        $this->fields_options = [
            'general' => [
                'title' => $this->trans('General preferences', [], 'Modules.Gamifications.Admin'),
                'fields' => [
                    GamificationsConfig::DISPLAY_HELP => [
                        'title' => $this->trans('Back Office help', [], 'Modules.Gamifications.Admin'),
                        'hint' =>
                            $this->trans('Choose whether to display various help messages & explanations about module', [], 'Modules.Gamifications.Admin'),
                        'validation' => 'isBool',
                        'type' => 'bool',
                    ],
                    GamificationsConfig::FRONT_OFFICE_TITLE => [
                        'title' => $this->trans('Front Office title', [], 'Modules.Gamifications.Admin'),
                        'hint' => $this->trans('Title that are displayed in Front Office account page', [], 'Modules.Gamifications.Admin'),
                        'validation' => 'isGenericName',
                        'type' => 'textLang',
                        'class' => 'fixed-width-lg'
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Modules.Gamifications.Admin'),
                ],
            ],
            'activities' => [
                'title' => $this->trans('Activity preferences'),
                'fields' => [
                    GamificationsConfig::DAILY_REWARDS_STATUS => [
                        'title' => $this->trans('Enable Daily Rewards', [], 'Modules.Gamifications.Admin'),
                        'validation' => 'isBool',
                        'type' => 'bool',
                    ],
                    GamificationsConfig::REFERRAL_PROGRAM_STATUS => [
                        'title' => $this->trans('Enable Refferal Program', [], 'Modules.Gamifications.Admin'),
                        'validation' => 'isBool',
                        'type' => 'bool',
                    ],
                    GamificationsConfig::SHOPPING_POINTS_STATUS => [
                        'title' => $this->trans('Enable Shopping points', [], 'Modules.Gamifications.Admin'),
                        'validation' => 'isBool',
                        'type' => 'bool',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Modules.Gamifications.Admin'),
                ],
            ],
        ];
    }
}
