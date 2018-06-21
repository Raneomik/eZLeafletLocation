<?php

require 'autoload.php';

$cli  = eZCLI::instance();
$endl = $cli->endlineString();

$script = eZScript::instance([
        'description'    => "eZ Publish eZLeafletLocation replacement with eZGmapLocation script\n" .
            "Replaces class and object attributes",
        'use-session'    => false,
        'use-modules'    => false,
        'use-extensions' => true,
    ]
);

$script->startup();

$options = $script->getOptions(
    "[n|dry-run][no-php-verbosity][p|progress][ci:|class-id:]", [
    'dry-run',
    'no-php-verbosity',
    'progress',
    'class-id',
], [
    'dry-run'          => "dry run mode",
    'no-php-verbosity' => "no php verbosity",
    'progress'         => "show progress",
    'class-id'         => "specified class(es) to update - coma separated if several. If none provided, all classes and objects will be processed",
]);

$script->initialize();

$cli->notice('Starting eZLeafletLocation attribute replacement with eZGmapLocation...');

if(!class_exists(eZGmapLocation::class)){
    $cli->notice("This script needs 'eZGmapLocation' extension to run... quitting");
    $script->shutdown();
}

$dryRun = $options['dry-run'];
if ($dryRun) {
    $cli->notice('running in dry-run mode');
}

if ($options['no-php-verbosity']) {
    error_reporting(0);
}

$params = [
    'IgnoreVisibility' => true,
];

if ($options['class-id']) {
    $params = array_merge($params, [
        'ClassFilterType'  => 'include',
        'ClassFilterArray' => explode(',', $options['class-id']),
    ]);
}

$nodeArray        = eZContentObjectTreeNode::subTreeByNodeID($params, 1);
$processedClasses = [];

$classesToProcess = [];
$nodesToProcess   = [];
$total = count($nodeArray);
$progressbar = null;
if ($options['progress']) {
    $out         = new ezcConsoleOutput();
    $progressbar = new ezcConsoleProgressbar($out, $total);
}

$cli->output("Calculating classes and nodes to process...");
foreach ($nodeArray as $node) {
    $classToProcess = checkForConcernedClass($node);
    if (! empty($classToProcess) && ! in_array($classToProcess, $classesToProcess)) {
        $classesToProcess[] = $classToProcess;
    }

    $nodeToProcess = checkForConcernedNode($node);
    if (! empty($nodeToProcess)) {
        $nodesToProcess[] = $nodeToProcess;
    }

    if ($progressbar) {
        $progressbar->advance();
    }
}

if ($progressbar) {
    $progressbar->finish();
    $cli->notice($endl);
}

$classTotal  = count($classesToProcess);
$nodeTotal   = count($nodesToProcess);
$total       = $classTotal + $nodeTotal;
if ($options['progress']) {
    $out         = new ezcConsoleOutput();
    $progressbar = new ezcConsoleProgressbar($out, $total);
}


$i = 0;

$process = $classTotal == 0 ? "nothing to process" : '';
$cli->output("Processing classes ..." . $process);

foreach ($classesToProcess as $class) {
    $progress = $progressbar ? "\n\n" : "[" . ++$i . " / $total] -";

    $cli->output("$progress processing class '" . $class->attribute('name') . "'");

    processClass($class, $dryRun, $cli);

    if ($progressbar) {
        $progressbar->advance();
    }
}

$cli->notice($endl);
$cli->notice($endl);
$process = $nodeTotal== 0 ? " nothing to process" : '';
$cli->output("Processing nodes ..." . $process);

foreach ($nodesToProcess as $node) {
    $progress = $progressbar ? "\n\n" : "[" . ++$i . " / $total] -";

    $cli->output(
        "$progress processing node '" . $node->attribute('name') .
        "' ([" . $node->attribute('class_name') . '], nodeId: ' .
        $node->attribute('node_id') . ")"
    );

    processNode($node, $dryRun, $cli);

    if ($progressbar) {
        $progressbar->advance();
    }
}

if ($progressbar && $total > 0) {
    $progressbar->finish();
    $cli->notice($endl);
}

if (! $dryRun && $total > 0) {
    $cli->notice('Clearing cache...');
    eZCache::clearAll();
}

$cli->notice('Done!');
$script->shutdown();

/**
 * @param eZContentClass $class
 * @param $dryRun
 * @param eZCLI $cli
 * @return null
 * @internal param eZContentObjectTreeNode $node
 * @internal param $processedClasses
 */
function processClass(eZContentClass $class, $dryRun, eZCLI $cli)
{
    /** @var eZContentClassAttribute $attribute */
    foreach ($class->fetchAttributes() as $attribute) {
        if ($attribute->attribute('data_type_string') == 'ezleafletlocation') {
            if (! $dryRun) {
                $attribute->setAttribute('data_type_string', 'ezgmaplocation');
                $attribute->sync();
            }
        }
    }
}

/**
 * @param eZContentObjectTreeNode $node objectT
 * @param $dryRun
 * @param $cli
 */
function processNode(eZContentObjectTreeNode $node, $dryRun, eZCLI $cli)
{
    /** @var eZContentObjectAttribute $attribute */
    foreach ($node->dataMap() as $attribute) {
        if ($attribute->attribute('data_type_string') == 'ezleafletlocation') {
            if (! $dryRun) {
                $content = $attribute->content();
                $attribute->setAttribute('data_type_string', 'ezgmaplocation');
                $attribute->setContent(
                    eZGmapLocation::create(
                        $content->contentobject_attribute_id,
                        $content->contentobject_version,
                        $content->latitude,
                        $content->longitude,
                        $content->street
                    )
                );
                $attribute->sync();
            }
        }
    }
}


function checkForConcernedClass(eZContentObjectTreeNode $node)
{
    $classToProcess = null;
    /** @var eZContentClass $class */
    $class = eZContentClass::fetchByIdentifier($node->attribute('class_identifier'));

    /** @var eZContentClassAttribute $attribute */
    foreach ($class->fetchAttributes() as $attribute) {
        if ($attribute->attribute('data_type_string') == 'ezleafletlocation') {
            $classToProcess = $class;
        }
    }
    return $classToProcess;
}


function checkForConcernedNode(eZContentObjectTreeNode $node)
{
    $nodeToProcess = null;
    /** @var eZContentObjectAttribute $attribute */
    foreach ($node->dataMap() as $attribute) {
        if ($attribute->attribute('data_type_string') == 'ezleafletlocation') {
            $nodeToProcess = $node;
        }
    }
    return $nodeToProcess;
}
