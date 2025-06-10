<?php

declare(strict_types=1);

namespace SportsScheduler\Resource;

use Exception;
use SportsPlanning\Field;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Poule;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsOneWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstTwoVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;

final class Fields
{
    /**
     * @var list<Field>
     */
    private array $unassignedFields;
    /**
     * @var list<Field>
     */
    private array $assignedFields = [];
    /**
     * @var array<string, bool>|null
     */
    private array|null $fieldPouleMap = null;

    public function __construct(PlanningConfiguration $configuration, Planning $planning)
    {
        $this->unassignedFields = $planning->getFields();
        $this->initFieldPouleMap($configuration, $planning);
    }

    private function initFieldPouleMap(PlanningConfiguration $configuration, Planning $planning): void
    {
        if (count($planning->sports) > 1
            || !$configuration->pouleStructure->isBalanced()
            || $configuration->perPoule) {
            return;
        }
        $fields = $planning->getFields();
        $poules = $planning->poules;
        $nrOfFields = count($fields);
        $nrOfPoules = count($poules);
        // poules A,B en fields 1,2,3,4,5,6    :   A1, A2, A3, B4, B5, B6
        if ($nrOfFields >= $nrOfPoules && ($nrOfFields % $nrOfPoules) === 0) {
            $this->fieldPouleMap = [];
            $nrOfFieldsPerPoule = (int)($nrOfFields / $nrOfPoules);
            foreach ($fields as $field) {
                $rest = $field->fieldNr % $nrOfFieldsPerPoule;
                $addToCeil = $rest === 0 ? 0 : ($nrOfFieldsPerPoule - $rest);
                $pouleNr = (int) (($field->fieldNr + $addToCeil)  / $nrOfFieldsPerPoule);
                $poule = $planning->getPoule($pouleNr);
                $index = $this->getFieldPouleMapIndex($field->fieldNr, $poule->pouleNr);
                $this->fieldPouleMap[$index] = true;
            }
        } elseif ($nrOfFields < $nrOfPoules && ($nrOfPoules % $nrOfFields) === 0) {
            // poules A,B,C,D en fields 1, 2   :   A1, B1, C2, D2
            $this->fieldPouleMap = [];
            $sport = $planning->getSport(1);
            $nrOfPoulesPerField = (int)($nrOfPoules / $nrOfFields);
            foreach ($poules as $poule) {
                $rest = $poule->pouleNr % $nrOfPoulesPerField;
                $addToCeil = $rest === 0 ? 0 : ($nrOfPoulesPerField - $rest);
                $fieldNr = (int) (($poule->pouleNr + $addToCeil)  / $nrOfPoulesPerField);
                $field = $sport->getField($fieldNr);
                $index = $this->getFieldPouleMapIndex($field->fieldNr, $poule->pouleNr);
                $this->fieldPouleMap[$index] = true;
            }
        }
    }



    /**
     * @param TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields $sportWithNrAndFields
     * @return list<Field>
     */
    public function getAssignableFields(TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields $sportWithNrAndFields): array
    {
        return array_values(array_filter( $sportWithNrAndFields->fields, function (Field $field): bool {
            return $this->isUnassigned($field);
        }));
    }

    public function isSomeFieldAssignable(int $sportNr, int $pouleNr): bool
    {
        foreach ($this->unassignedFields as $unassignedField) {
            if ($this->isFieldAssignable($unassignedField, $sportNr, $pouleNr)) {
                return true;
            }
        }
        return false;
    }

    public function assignToGame(TogetherGame|AgainstGame $game): void
    {
        foreach ($this->unassignedFields as $unassignedField) {
            if (!$this->isFieldAssignable($unassignedField, $game->getField()->sportNr, $game->pouleNr)) {
                continue;
            }
            $this->assign($unassignedField);
            $game->setField($unassignedField);
            return;
        }
        throw new Exception('no field could be assigned', E_ERROR);
    }

    public function fill(): void
    {
        while ($assignedField = array_shift($this->assignedFields)) {
            array_push($this->unassignedFields, $assignedField);
        }
    }

    protected function isUnassigned(Field $field): bool
    {
        $idx = array_search($field, $this->unassignedFields, true);
        return $idx !== false;
    }

    protected function assign(Field $field): void
    {
        $idx = array_search($field, $this->assignedFields, true);
        if ($idx !== false) {
            throw new Exception('field could be assigned', E_ERROR);
        }
        $idx = array_search($field, $this->unassignedFields, true);
        if ($idx === false) {
            throw new Exception('field is not unassigned', E_ERROR);
        }
        array_splice($this->unassignedFields, $idx, 1);
        array_push($this->assignedFields, $field);
    }

    /*public function unassign(Field $field): void
    {
        $idx = array_search($field, $this->unassignedFields, true);
        if ($idx !== false) {
            throw new \Exception('field is already unassigned', E_ERROR);
        }
        $idx = array_search($field, $this->assignedFields, true);
        if ($idx === false) {
            throw new \Exception('field is not yet assigned', E_ERROR);
        }
        array_splice($this->assignedFields, $idx, 1);
        array_push($this->unassignedFields, $field);
    }*/

    protected function isFieldAssignable(Field $field, int $sportNr, int $pouleNr): bool
    {
        if ($field->sportNr !== $sportNr) {
            return false;
        }
        return $this->fieldPouleMap === null|| isset($this->fieldPouleMap[$this->getFieldPouleMapIndex($field->fieldNr, $pouleNr)]);
    }

    protected function getFieldPouleMapIndex(int $fieldNr, int $pouleNr): string
    {
        return 'P' . $pouleNr . '-F' . $fieldNr;
    }

//    public function copy(Planning $planning): Fields
//    {
//        $fields = new Fields($planning);
//        foreach (array_reverse($this->assignedFields) as $field) {
//            $fields->assign($field);
//        }
//        return $fields;
//    }
}
