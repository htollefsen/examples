<?php

namespace Transfer\Examples\YamlToEzPlatformContentType\Manifest;

use eZ\Publish\API\Repository\Repository;
use Transfer\Commons\Stream\Adapter\StreamAdapter;
use Transfer\Commons\Yaml\Worker\Transformer\YamlToArrayTransformer;
use Transfer\EzPlatform\Adapter\EzPlatformAdapter;
use Transfer\EzPlatform\Worker\Transformer\ArrayToEzPlatformContentTypeObjectTransformer;
use Transfer\Manifest\AbstractManifest;
use Transfer\Manifest\ManifestInterface;
use Transfer\Procedure\ProcedureBuilder;
use Transfer\Processor\EventDrivenProcessor;
use Transfer\Processor\ProcessorInterface;
use Transfer\Processor\SequentialProcessor;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class YamlToEzPlatformContentTypeManifest implements ManifestInterface
{

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var SequentialProcessor
     */
    protected $processor;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->processor = new SequentialProcessor();
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'yaml_to_ezplatform_contenttype';
    }

    /**
     * @inheritdoc
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @inheritdoc
     */
    public function configureProcedureBuilder(ProcedureBuilder $builder)
    {
        $builder
            ->createProcedure('import')
                ->createProcedure('contenttype')
                    ->addSource(new StreamAdapter(fopen(__DIR__.'/../resources/yaml/detailed.yml', 'r')))
                        ->addWorker(new YamlToArrayTransformer())
                        ->AddWorker(new ArrayToEzPlatformContentTypeObjectTransformer())
                    ->addTarget(new EzPlatformAdapter(array('repository' => $this->repository)))
                ->end()
            ->end()
        ;
    }

    /**
     * @inheritdoc
     */
    public function configureProcessor(ProcessorInterface $processor)
    {
        $logger = new Logger('default');
        $logger->pushHandler(new StreamHandler(sprintf('%s/%s.log', 'ezpublish/logs/transfer/contenttype', date('Y-m-d')), Logger::DEBUG));
        if ($processor instanceof EventDrivenProcessor) {
            $processor->setLogger($logger);
        }
    }

}