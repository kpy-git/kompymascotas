<?php

namespace PrestaShop\Module\KpyFaq\Repository;

use Db;
use PrestaShop\Module\KpyFaq\Entity\KpyFaqElement;
use PrestaShop\Module\KpyFaq\Exception\KpyFaqException;

class KpyFaqElementRepository
{
    /**
     * @throws \PrestaShopException
     * @throws KpyFaqException
     */
    public function findById(int $idElement, int $idLang): KpyFaqElement
    {
        $result = Db::getInstance()->getRow(
            "SELECT fe.id_element, fel.question, fel.answer, fe.id_section , fel.link_rewrite 
                FROM `" . _DB_PREFIX_ . "kpy_faq_element` fe
                INNER JOIN `" . _DB_PREFIX_ . "kpy_faq_element_lang` fel 
                    ON fel.id_element = fe.id_element
                        AND fel.id_lang = $idLang
                WHERE fe.id_element = $idElement and fe.active = 1
                ORDER BY fe.position"
        );

        if (empty($result)) {
            throw new KpyFaqException('Not exists element with id ' . $idElement);
        }

        return (new KpyFaqElement())
            ->setId($result['id_element'])
            ->setQuestion($result['question'])
            ->setAnswer($result['answer'])
            ->setSectionId($result['id_section'])
            ->setLinkRewrite($result['link_rewrite']);
    }

    /**
     * @param int $idSecion
     * @param int $idLang
     * @return KpyFaqElement[]
     */
    public function findAllBySection(int $idSecion, int $idLang): array
    {
        $results = Db::getInstance()->executeS(
            "SELECT fe.id_element, fel.question, fel.answer, fel.link_rewrite 
                FROM " . _DB_PREFIX_ . "kpy_faq_element fe
                INNER JOIN " . _DB_PREFIX_ . "kpy_faq_element_lang fel 
                    ON fel.id_element = fe.id_element
                        AND fel.id_lang = $idLang
                WHERE fe.id_section = $idSecion and fe.active = 1
                ORDER BY fe.position"
        );

        if (empty($results)) {
            return [];
        }

        return array_map(static function (array $result) {
            return (new KpyFaqElement())
                ->setId($result['id_element'])
                ->setQuestion($result['question'])
                ->setLinkRewrite($result['link_rewrite'])
                ->setAnswer($result['answer']);
        }, $results);
    }

    public function findByLinkRewrite(string $link_rewrite, int $idLang): KpyFaqElement
    {
        $idElement = Db::getInstance()->getValue(
            "SELECT id_element 
                FROM " . _DB_PREFIX_ . "kpy_faq_element_lang 
                WHERE link_rewrite = '" . pSQL($link_rewrite) . "' 
                    AND id_lang = $idLang"
        );

        if (!$idElement) {
            throw new KpyFaqException('Element not found');
        }

        return $this->findById($idElement, $idLang);
    }
}