final <?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\TestAsset;

class SerializableTestClass
{
    protected string $property;

    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}
