<?php

declare(strict_types=1);

namespace Lits\Action;

use League\Period\Datepoint;
use Lits\Config\BookConfig;
use Lits\LibCal\Data\Space\FormSpaceData;
use Lits\Meta\CategoryMeta;
use Lits\Meta\ItemMeta;
use Lits\Meta\LocationMeta;
use Slim\Exception\HttpInternalServerErrorException;

use function Safe\preg_split;

trait TraitBookTime
{
    use TraitBookItem;

    /** @throws HttpInternalServerErrorException */
    private function findForm(
        LocationMeta $location,
        CategoryMeta $category,
        ItemMeta $item
    ): FormSpaceData {
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $formid = $item->data->formid ?? 0;

        if ($formid === 0) {
            $formid = $category->data->formid ?? 0;
        }

        if ($formid === 0) {
            $formid = $location->data->formid ?? 0;
        }

        $form = false;

        try {
            if ($formid !== 0) {
                $result = $this->client->space()
                    ->form($formid)
                    ->cache()
                    ->send();

                $form = \reset($result);
            }

            if ($form === false) {
                $form = FormSpaceData::fromArray([
                    'id' => 0,
                    'name' => 'Name & Email',
                    'fields' => [
                        'fname' => [
                            'label' => 'First Name',
                            'type' => 'string',
                            'required' => true,
                        ],
                        'lname' => [
                            'label' => 'Last Name',
                            'type' => 'string',
                            'required' => true,
                        ],
                        'email' => [
                            'label' => 'Email',
                            'type' => 'string',
                            'required' => true,
                        ],
                    ],
                ]);
            }
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        foreach ($this->settings['book']->questions as $question) {
            if (isset($form->fields[$question->id])) {
                $form->fields[$question->id]->type = $question->type;
            }
        }

        return $form;
    }

    /**
     * @return string[]
     * @throws HttpInternalServerErrorException
     */
    private function findOptions(ItemMeta $item, Datepoint $datepoint): array
    {
        $options = [];

        $maximum = $datepoint
            ->add($item->lengthMaximum ?? $item->lengthDivisor)
            ->sub($item->lengthMinimum ?? $item->lengthDivisor);

        $date = $datepoint->format('Y-m-d');
        $current = clone $datepoint;

        while ($current <= $maximum) {
            $time = $current->format('H:i');

            if (
                !isset($item->times[$date][$time]) ||
                !$item->times[$date][$time]
            ) {
                break;
            }

            $end = $current->add($item->lengthMinimum ?? $item->lengthDivisor);
            $options[$end->format('c')] = self::hoursAndMinutes(
                $end->diff($datepoint)
            );

            $current = $current->add($item->lengthDivisor);
        }

        return $options;
    }

    /**
     * @return array{pattern?:string,title?:string}
     * @throws HttpInternalServerErrorException
     */
    private function findEmail(ItemMeta $item): array
    {
        if (\is_null($item->emailDomain)) {
            return [];
        }

        try {
            /** @var string[] */
            $domains = preg_split('/,\s*/', \preg_quote($item->emailDomain));

            return [
                'pattern' => '.*(' . \implode('|', $domains) . ')',
                'title' => 'Enter ' . $item->emailDomain . ' addresses only',
            ];
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }

    private static function hoursAndMinutes(\DateInterval $interval): string
    {
        $result = '';

        if ($interval->h > 0) {
            $result = $interval->format('%h Hour');

            if ($interval->h > 1) {
                $result .= 's';
            }

            if ($interval->i > 0) {
                $result .= ', ';
            }
        }

        if ($interval->i > 0) {
            $result .= $interval->format('%i Minute');

            if ($interval->i > 1) {
                $result .= 's';
            }
        }

        return $result;
    }
}
