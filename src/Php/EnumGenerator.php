<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php;

use Laminas\Code\Generator\EnumGenerator as LaminasEnumGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\DocBlockGenerator;

/**
 * Generates a PHP 8.1+ enum (unit or backed) using Laminas Code.
 */
class EnumGenerator
{
    /**
     * Given a list of XSD-style enumerations (each with 'value' and 'doc'),
     * transform them into a valid array of cases (with 'name' and 'value')
     * and call generateEnum().
     *
     * @param string $namespace     The fully qualified namespace (e.g., "App\\Enums")
     * @param string $enumName      The name of the enum (e.g., "OperationMode")
     * @param array  $xsdEnumValues An array like:
     *                             [
     *                               [ 'value' => 'normal',   'doc' => '' ],
     *                               [ 'value' => 'extended', 'doc' => '' ],
     *                               ...
     *                             ]
     * @param string|null $description Optional docblock for the enum
     *
     * @return string Generated PHP code for the enum
     */
    public function generateEnumFromXsdValues(
        string $namespace,
        string $enumName,
        array $xsdEnumValues,
        ?string $description = null
    ): string {
        // Convert XSD enumerations to the format expected by generateEnum().
        $cases = [];
        foreach ($xsdEnumValues as $entry) {
            // 'value' => e.g. "normal"
            $rawValue = $entry['value'] ?? '';

            // Build a case "name" from the rawValue (e.g., "NORMAL")
            $caseName = $this->makeCaseName($rawValue);

            $cases[] = [
                'name'  => $caseName,
                'value' => $rawValue,           // The string/int to be assigned as the enum's backed value
                'doc'   => $entry['doc'] ?? '', // Not used in final code, but we keep it for reference
            ];
        }

        // Now generate the actual enum code
        return $this->generateEnum($namespace, $enumName, $cases, $description);
    }

    /**
     * Generate a PHP enum (unit or backed) source code string.
     *
     * Expected $cases format:
     *   [
     *     [ 'name' => 'NORMAL',   'value' => 'normal' ],
     *     [ 'name' => 'EXTENDED', 'value' => 'extended' ],
     *     ...
     *   ]
     *
     * If 'value' is omitted, it becomes a pure "unit" case.
     * If 'value' is a string => string-backed enum
     * If 'value' is an int => int-backed enum
     *
     * @param string      $namespace    e.g. "App\\Enums"
     * @param string      $enumName     e.g. "OperationMode"
     * @param array       $cases        The transformed cases
     * @param string|null $description  Optional docblock for the enum
     *
     * @throws \InvalidArgumentException if a mix of string and int is found
     */
    public function generateEnum(
        string $namespace,
        string $enumName,
        array $cases,
        ?string $description = null
    ): string {
        // 1) Determine if we should generate a string-backed, int-backed, or unit enum
        $backedType = $this->determineEnumType($cases);

        // 2) Create the Laminas EnumGenerator
        $enumGenerator = new LaminasEnumGenerator();
        $enumGenerator->setName($enumName);
        $enumGenerator->setNamespaceName($namespace);

        // If it's string or int, set the appropriate backed type
        if ($backedType !== null) {
            $enumGenerator->setBackedType($backedType);
        }

        // 3) Create a docblock for the enum
        $docBlock = new DocBlockGenerator($description ?: "Enum $enumName");
        $enumGenerator->setDocBlock($docBlock);

        // 4) Add each case to the enum
        foreach ($cases as $case) {
            $caseName  = $case['name'];
            $caseValue = $case['value'] ?? null;

            if ($caseValue !== null) {
                // e.g. case NORMAL = 'normal';
                $enumGenerator->addCase($caseName, $caseValue);
            } else {
                // e.g. case NORMAL;
                $enumGenerator->addCase($caseName);
            }
        }

        // 5) Wrap it in a FileGenerator to produce valid PHP code
        $fileGenerator = new FileGenerator();
        $fileGenerator->setClass($enumGenerator);

        return $fileGenerator->generate();
    }

    /**
     * Determine the enum type by scanning all provided cases:
     *   - If any case has a string value, the entire enum is string-backed.
     *   - If any case has an int value, the entire enum is int-backed.
     *   - If no cases have values, it's a unit enum (no backing type).
     *
     * @param array $cases
     * @return null|string 'string', 'int', or null if a unit enum
     * @throws \InvalidArgumentException if both string and int values are found
     */
    private function determineEnumType(array $cases): ?string
    {
        $foundStrings = false;
        $foundInts    = false;

        foreach ($cases as $case) {
            if (array_key_exists('value', $case) && $case['value'] !== null) {
                if (is_int($case['value'])) {
                    $foundInts = true;
                } elseif (is_string($case['value'])) {
                    $foundStrings = true;
                } else {
                    // Some other type => not a valid backed enum in PHP
                    throw new \InvalidArgumentException(
                        'Enum values must be string or int when specified.'
                    );
                }
            }
        }

        // If there's a mix, throw an exception
        if ($foundStrings && $foundInts) {
            throw new \InvalidArgumentException(
                'Cannot create an enum with both string and int values.'
            );
        }

        if ($foundStrings) {
            return 'string';
        }
        if ($foundInts) {
            return 'int';
        }

        // No values => pure unit enum
        return null;
    }

    /**
     * Convert an XSD enumeration value string to a valid PHP enum case name.
     *
     * Example:
     *   "normal"   => "NORMAL"
     *   "extended" => "EXTENDED"
     *   "123"      => "_123"
     *
     * Adjust as needed for your naming conventions.
     *
     * @param string $value
     * @return string
     */
    private function makeCaseName(string $value): string
    {
        // Replace any non-alphanumeric chars with underscores, then uppercase.
        // If the first character is a digit, prepend an underscore.
        $name = preg_replace('/[^a-zA-Z0-9]+/', '_', $value);

        // If it starts with a digit, prepend an underscore
        if (preg_match('/^\d/', $name)) {
            $name = '_' . $name;
        }

        return strtoupper($name);
    }
}
