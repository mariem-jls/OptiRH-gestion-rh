<?php
namespace App\Twig;

use Endroid\QrCode\Builder\BuilderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class QrcodeExtension extends AbstractExtension
{
    private BuilderInterface $qrCodeBuilder;

    public function __construct(BuilderInterface $qrCodeBuilder)
    {
        $this->qrCodeBuilder = $qrCodeBuilder;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('qr_code_data_uri', [$this, 'generateQrCodeDataUri']),
        ];
    }

    public function generateQrCodeDataUri(string $data, array $options = []): string
    {
        $builder = $this->qrCodeBuilder
            ->data($data)
            ->size($options['size'] ?? 200)
            ->margin($options['margin'] ?? 10);

        return $builder->build()->getDataUri();
    }
}