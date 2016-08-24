<?php

namespace AppBundle\Command;

use \Dropbox as dbx;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackupDbCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('script:backup_db')
            ->setDescription('Backup databases')
            ->addArgument('env', InputArgument::REQUIRED, 'The environment')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getArgument('env');
        $output->writeln('<comment>Get parameters</comment>');
        $args = $this->getDbParameters($env);
        
        $dbCount = count($args[$env]['databases']); 
        foreach ($args[$env]['databases'] as $key => $database) {
            $execOutput = array();
            $current = $key + 1;
            $output->writeln("<comment>{$current} / {$dbCount}</comment>");
            
            // Gzip
            $output->writeln("<comment>Generate gzip for {$database}</comment>");
            $output->writeln("<question>".$this->getContainer()->getParameter('kernel.root_dir'). "/Resources/scripts/backup_db.sh {$args[$env]['folder']}{$database} {$database} {$args[$env]['filetokeep']}</question>");
            exec($this->getContainer()->getParameter('kernel.root_dir'). "/Resources/scripts/backup_db.sh {$args[$env]['folder']}{$database} {$database} {$args[$env]['filetokeep']}", $execOutput);
            $output->writeln("<info>Gzip created</info>");

            // Dropbox
            $output->writeln("<comment>Upload to dropbox</comment>");
            $this->uploadDropbox(
                $args[$env]['folder']. $database. '/'. $execOutput[0],
                $this->getContainer()->getParameter('backup_db_dropbox_folder'). $env. '/'. $database. '/'. $execOutput[0]
            );
            $output->writeln("<info>Upload done</info>");
            $output->writeln("<comment>Check delete from dropbox</comment>");
            $this->deleteDropbox(
                $this->getContainer()->getParameter('backup_db_dropbox_folder'). $env. '/'. $database,
                $args[$env]['filetokeep']
            );
            $output->writeln("<info>Check delete from dropbox done</info>");
        }
    }
    
    private function uploadDropbox($file, $dbFile)
    {
        $dbxClient = new dbx\Client($this->getContainer()->getParameter('dropbox_api_access_token'), "PHP-Example/1.0");
        $f = fopen($file, "rb");
        $result = $dbxClient->uploadFile($dbFile, dbx\WriteMode::add(), $f);
        fclose($f);
    }
    
    private function deleteDropbox($dbFolder, $fileToKeep)
    {
        $dbxClient = new dbx\Client($this->getContainer()->getParameter('dropbox_api_access_token'), "PHP-Example/1.0");
        $metadata = $dbxClient->getMetadataWithChildren($dbFolder);
        $contents = $metadata['contents'];

        if (count($contents) > $fileToKeep) {
           $deleteCount = count($contents) - $fileToKeep;
           for ($x = 0; $x != $deleteCount; $x++) {
               $dbxClient->delete($contents[$x]['path']);
           }
        }
    }

    private function getDbParameters($env) {
        $args[$env] = array (
            'folder' => $this->getContainer()->getParameter("backup_db_{$env}_folder"),
            'databases' => $this->getContainer()->getParameter("backup_db_{$env}_databases"),
            'filetokeep' => $this->getContainer()->getParameter("backup_db_{$env}_filetokeep"),
        );

        return $args;
    }
}