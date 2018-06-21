<?php

require 'autoload.php';

$cli  = eZCLI::instance();
$endl = $cli->endlineString();

$script = eZScript::instance([
        'description'    => "eZ Publish eZGmapLocation replacement with eZLeafletLocation script\n" .
            "Replaces class and object attributes",
        'use-session'    => false,
        'use-modules'    => false,
        'use-extensions' => true,
    ]
);

$script->startup();

$options = $script->getOptions(
    "[n|dry-run][no-php-verbosity][p|progress][ci:|class-id:]", [
    'dry-run','no-php-verbosity','progress','class-id'], [
    'dry-run'          => "dry run mode",
    'no-php-verbosity' => "no php verbosity",
    'progress'         => "show progress",
    'class-id'         => "specified class(es) to update - coma separated if several. If none provided, all classes and objects will be processed",
]);

$script->initialize();

$cli->notice('Starting eZGmapLocation attribute replacement with eZLeafletLocation...');


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

$total = count($nodeArray);
$progressbar = null;
if ($options['progress']) {
    $out         = new ezcConsoleOutput();
    $progressbar = new ezcConsoleProgressbar($out, $total);
}

$i = 0;
foreach ($nodeArray as $node) {
    $progress = $progressbar ? "\n\n" : "[" . ++$i . " / $total] -";

    $cli->output(
        "$progress processing '" . $node->attribute('name') .
        "' ([" . $node->attribute('class_name') . '], nodeId: ' .
        $node->attribute('node_id') . ")"
    );

    if(!$dryRun){
        processClass($node, $processedClasses, $dryRun, $cli);
        processNode($node, $dryRun, $cli);
    } else {
        usleep(10000);
    }

    if ($progressbar) {
        $progressbar->advance();
    }
}

if ($progressbar) {
    $progressbar->finish();
    $cli->notice($endl);
}

if (! $dryRun) {
    $cli->notice('Clearing cache...');
    eZCache::clearAll();
}

$cli->notice('Done!');
$script->shutdown();

/**
 * @param eZContentObjectTreeNode $node
 * @param $processedClasses
 * @param $dryRun
 * @param $cli
 * @return null
 */
function processClass(eZContentObjectTreeNode $node, &$processedClasses, $dryRun, eZCLI $cli)
{
    $class_id = $node->attribute('class_identifier');
    if (! in_array($class_id, $processedClasses)) {
        /** @var eZContentClass $class */
        $class = eZContentClass::fetchByIdentifier($class_id);

        /** @var eZContentClassAttribute $attribute */
        foreach ($class->fetchAttributes() as $attribute) {
            if ($attribute->attribute('data_type_string') == 'ezgmaplocation') {
                $cli->style('notice');
                $cli->output( $attribute->attribute('name') . " [$class_id] has 'ezgmaplocation' attr.. Processing change..." );
                $cli->style('notice-end');

                if(! $dryRun){
                    $attribute->setAttribute('data_type_string', 'ezleafletlocation');
                    $attribute->sync();
                }
            }
        }
        $processedClasses[] = $class_id;
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
        if ($attribute->attribute('data_type_string') == 'ezgmaplocation') {
            $cli->style('notice');
            $cli->output( $node->attribute('name') . " has 'ezgmaplocation' attr.. Processing change..." );
            $cli->style('notice-end');
            if(!$dryRun){
                $content = $attribute->content();
                $attribute->setAttribute('data_type_string', 'ezleafletlocation');
                $attribute->setContent(
                    eZLeafletLocation::create(
                        $content->contentobject_attribute_id,
                        $content->contentobject_attribute_version,
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