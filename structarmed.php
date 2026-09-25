<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception')
    ->layer('Generator', 'src/Generator')
    ->layer('Value', [
        'src/AbstractValue.php',
        'src/Value',
    ])
    ->layer('Message', [
        'src/Fault.php',
        'src/Request.php',
        'src/Response.php',
    ])
    ->layer('Request', 'src/Request')
    ->layer('Response', 'src/Response')
    ->layer('ClientException', 'src/Client/Exception')
    ->layer('Client', ['src/Client.php', 'src/Client'], 'src/Client/Exception')
    ->layer('ServerException', 'src/Server/Exception')
    ->layer('Server', ['src/Server.php', 'src/Server'], 'src/Server/Exception')
    ->ruleset([
        'Exception'       => [],
        'Generator'       => [],
        'Value'           => ['Exception', 'Generator'],
        'Message'         => ['+Value'],
        'Request'         => ['+Message'],
        'Response'        => ['+Message'],
        'ClientException' => ['Exception'],
        'Client'          => ['+ClientException', '+Message'],
        'ServerException' => ['Exception'],
        'Server'          => ['+Request', '+Response', '+ServerException'],
    ]);
