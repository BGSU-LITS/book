<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Config\BookConfig;
use Lits\LibCal\Data\Space\FormSpaceData;
use Lits\LibCal\Data\Space\QuestionSpaceData;
use Lits\Meta\CategoryMeta;
use Lits\Meta\ItemMeta;
use Lits\Meta\LocationMeta;
use Safe\DateTimeImmutable;
use Slim\Exception\HttpInternalServerErrorException;

use function Safe\preg_split;

trait TraitBookTime
{
    use TraitBookItem;

    /** @throws HttpInternalServerErrorException */
    private function findForm(
        LocationMeta $location,
        CategoryMeta $category,
        ItemMeta $item,
    ): FormSpaceData {
        $formid = $item->data->formid ?? 0;

        if ($formid === 0) {
            $formid = $category->data->formid ?? 0;
        }

        if ($formid === 0) {
            $formid = $location->data->formid ?? 0;
        }

        $form = $this->findFormById($formid);

        return $this->modifyForm($form, $item);
    }

    /** @throws HttpInternalServerErrorException */
    private function findFormById(int $formid): FormSpaceData
    {
        try {
            $form = false;

            if ($formid !== 0) {
                $result = $this->client->space()
                    ->form($formid)
                    ->cache()
                    ->send();

                $form = \reset($result);
            }

            if ($form !== false) {
                return $form;
            }

            return FormSpaceData::fromArray([
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
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @return array<string>
     * @throws HttpInternalServerErrorException
     */
    private function findOptions(
        ItemMeta $item,
        DateTimeImmutable $datetime,
    ): array {
        $options = [];

        try {
            $maximum = $datetime
                ->add($item->lengthMaximum())
                ->sub($item->lengthMinimum());

            $date = $datetime->format('Y-m-d');
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }

        $current = $datetime;

        while ($current <= $maximum) {
            $time = $current->format('H:i');

            if (
                !isset($item->times[$date][$time]) ||
                !$item->times[$date][$time]
            ) {
                break;
            }

            $end = $current->add($item->lengthMinimum());
            $options[$end->format('c')] = self::hoursAndMinutes(
                $end->diff($datetime),
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
            /** @psalm-var array<string> $domains */
            $domains = preg_split('/,\s*/', \preg_quote($item->emailDomain));

            return [
                'pattern' => '.*(' . \implode('|', $domains) . ')',
                'title' => 'Enter ' . $item->emailDomain . ' addresses only',
            ];
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /** @throws HttpInternalServerErrorException */
    private function modifyForm(
        FormSpaceData $form,
        ItemMeta $item,
    ): FormSpaceData {
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        foreach ($this->settings['book']->questions as $question) {
            if (isset($form->fields[$question->id])) {
                $form->fields[$question->id]->type = $question->type;
            }
        }

        if ($item->nicknameField !== null) {
            $form = $this->modifyFormNickname($form, $item);
        }

        return $form;
    }

    /** @throws HttpInternalServerErrorException */
    private function modifyFormNickname(
        FormSpaceData $form,
        ItemMeta $item,
    ): FormSpaceData {
        try {
            $nickname = QuestionSpaceData::fromArray([
                'label' => $item->nicknameField,
                'type' => 'string',
                'required' => $item->nicknameRequired,
            ]);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }

        $fields = $form->fields;
        $form->fields = [];

        foreach ($fields as $key => $value) {
            $form->fields[$key] = $value;

            if ($key === 'email') {
                $form->fields['nickname'] = $nickname;
            }
        }

        // Add to end of array if email field isn't found.
        $form->fields['nickname'] = $nickname;

        return $form;
    }

    private static function hoursAndMinutes(\DateInterval $interval): string
    {
        return self::hours($interval) . self::minutes($interval);
    }

    private static function hours(\DateInterval $interval): string
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

        return $result;
    }

    private static function minutes(\DateInterval $interval): string
    {
        $result = '';

        if ($interval->i > 0) {
            $result = $interval->format('%i Minute');

            if ($interval->i > 1) {
                $result .= 's';
            }
        }

        return $result;
    }
}
