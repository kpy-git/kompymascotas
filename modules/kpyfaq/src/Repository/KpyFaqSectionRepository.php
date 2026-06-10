<?php

namespace PrestaShop\Module\KpyFaq\Repository;

use Db;
use PrestaShop\Module\KpyFaq\Entity\KpyFaqSection;
use PrestaShop\Module\KpyFaq\Exception\KpyFaqException;

class KpyFaqSectionRepository
{
    /**
     * @param int $idLang
     * @return KpyFaqSection[]
     */
    public function findAll(int $idLang, bool $includeElements = false): array
    {
        $results = Db::getInstance()->executeS(
            "SELECT fs.id_section, fsl.name, fs.image 
                FROM `" . _DB_PREFIX_ . "kpy_faq_section` fs 
                INNER JOIN `" . _DB_PREFIX_ . "kpy_faq_section_lang` fsl
                    ON fsl.id_section = fs.id_section
                        AND fsl.id_lang = $idLang
                WHERE fs.active = 1 
                ORDER BY fs.position ASC"
        );

        if (empty($results)) {
            return [];
        }

        $sections = array_map(static function (array $row) {
            return (new KpyFaqSection())
                ->setId($row['id_section'])
                ->setTitle($row['name'])
                ->setImage($row['image'] ?? '');
        }, $results);

        if (!$includeElements) {
            return $sections;
        }

        $elementRepository = new KpyFaqElementRepository();

        foreach ($sections as $section) {
            $section->setElements($elementRepository->findAllBySection($section->getId(), $idLang));
        }

        return $sections;
    }

    public function findById(int $id, int $idLang, bool $includeElements = false): KpyFaqSection
    {
        $result = Db::getInstance()->getRow(
            "SELECT fs.id_section, fsl.name, fs.image 
                FROM `" . _DB_PREFIX_ . "kpy_faq_section` fs 
                INNER JOIN `" . _DB_PREFIX_ . "kpy_faq_section_lang` fsl
                    ON fsl.id_section = fs.id_section
                        AND fsl.id_lang = $idLang
                WHERE fs.id_section = $id"
        );

        if (empty($result)) {
            throw new KpyFaqException('Section not found');
        }

        $section = new KpyFaqSection();
        $section
            ->setId($id)
            ->setTitle($result['name'])
            ->setImage($result['image'] ?? '');

        if (!$includeElements) {
            return $section;
        }

        $elementRepository = new KpyFaqElementRepository();

        $section->setElements($elementRepository->findAllBySection($id, $idLang));

        return $section;
    }

    public function findWithinElementId(int $idElement, int $idLang): KpyFaqSection
    {
        $result = Db::getInstance()->getRow(
            "SELECT fs.id_section, fsl.name, fs.image
                FROM `" . _DB_PREFIX_ . "kpy_faq_section` fs
                INNER JOIN `" . _DB_PREFIX_ . "kpy_faq_section_lang` fsl
                    ON fsl.id_section = fs.id_section
                        AND fsl.id_lang = $idLang
                WHERE fs.id_section = (SELECT id_section
                    FROM ps_kpy_faq_element
                    where id_element = $idElement)"
        );

        return (new KpyFaqSection())
            ->setId($result['id_section'])
            ->setTitle($result['name'])
            ->setImage($result['image'] ?? '');
    }
}