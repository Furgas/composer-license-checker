<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use Dominikb\ComposerLicenseChecker\Exceptions\LogicException;
use Dominikb\ComposerLicenseChecker\Contracts\LicenseConstraintHandler;

class ConstraintViolationDetector implements LicenseConstraintHandler
{
    /** @var string[] */
    protected $blocklist = [];

    /** @var string[] */
    protected $allowlist = [];

    public function setBlocklist(array $licenses): void
    {
        $this->blocklist = $licenses;
    }

    public function setAllowlist(array $licenses): void
    {
        $this->allowlist = $licenses;
    }

    /**
     * @param Dependency[] $dependencies
     *
     * @return ConstraintViolation[]
     * @throws LogicException
     */
    public function detectViolations(array $dependencies): array
    {
        $this->ensureConfigurationIsValid();

        return [
            $this->detectBlocklistViolation($dependencies),
            $this->detectAllowlistViolation($dependencies),
        ];
    }

    /**
     * @throws LogicException
     */
    public function ensureConfigurationIsValid(): void
    {
        $overlap = array_intersect($this->blocklist, $this->allowlist);

        if (count($overlap) > 0) {
            $invalidLicenseConditionals = sprintf('"%s"', implode('", "', $overlap));
            throw new LogicException("Licenses must not be on the block- and allowlist at the same time: ${invalidLicenseConditionals}");
        }
    }

    /**
     * @param Dependency[] $dependencies
     */
    private function detectBlocklistViolation(array $dependencies): ConstraintViolation
    {
        $violation = new ConstraintViolation('Blocked license found!');

        if (! empty($this->blocklist)) {
            foreach ($dependencies as $dependency) {
                if ($this->allLicensesOnList($dependency->getLicenses(), $this->blocklist)) {
                    $violation->add($dependency);
                }
            }
        }

        return $violation;
    }

    /**
     * @param Dependency[] $dependencies
     */
    private function detectAllowlistViolation(array $dependencies): ConstraintViolation
    {
        $violation = new ConstraintViolation('Unallowed license found!');

        if (! empty($this->allowlist)) {
            foreach ($dependencies as $dependency) {
                if (! $this->anyLicenseOnList($dependency->getLicenses(), $this->allowlist)) {
                    $violation->add($dependency);
                }
            }
        }

        return $violation;
    }

    private function allLicensesOnList(array $licenses, array $list): bool
    {
        return count(array_intersect($licenses, $list)) === count($licenses);
    }

    private function anyLicenseOnList(array $licenses, array $list): bool
    {
        return count(array_intersect($licenses, $list)) > 0;
    }
}
