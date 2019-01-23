<?php

class CommandHandler
{
    protected $argv;
    protected $categoryTree;

    public function __construct(array $argv, CategoryTree $categoryTree)
    {
        $this->argv = $argv;
        $this->categoryTree = $categoryTree;
    }

    public function process()
    {
        try {
            switch ($this->argv[1]) {
                case 'addNode': $this->addNode($this->argv[2], $this->argv[3]); break;
                case 'deleteNode': $this->deleteNode($this->argv[2]); break;
                case 'renameNode': $this->renameNode($this->argv[2], $this->argv[3]); break;
                case 'moveNodeUp': $this->moveNodeUp($this->argv[2]); break;
                case 'moveNodeDown': $this->moveNodeDown($this->argv[2]); break;
                case 'showTree': break;
                default: throw new Exception('Invalid request');
            }

            $this->showTree();

        } catch (TypeError $e) {
            die('Error: Invalid request');
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    protected function addNode(string $title, int $parentNode = null)
    {
        $id = $this->categoryTree->add($title, $parentNode);
        echo "Node \"{$title}\" has been added with id #{$id} \n";
    }

    protected function deleteNode(int $id)
    {
        $this->categoryTree->delete($id);
        echo "Node id #{$id} has been deleted \n";
    }

    protected function renameNode(int $id, string $title)
    {
        $this->categoryTree->rename($id, $title);
        echo "Node id #{$id} has been renamed \n";
    }

    protected function moveNodeUp(int $id)
    {
        $this->categoryTree->moveNodeUp($id);
        echo "Node id #{$id} has been moved up \n";
    }

    protected function moveNodeDown(int $id)
    {
        $this->categoryTree->moveNodeDown($id);
        echo "Node id #{$id} has been moved down \n";
    }

    protected function showTree()
    {
        echo "\n";
        $tree = $this->categoryTree->getAll();

        if (!$tree) {
            echo "Tree is empty\n";
            return false;
        }

        foreach ($tree as $node) {
            for ($i = 0; $i < $node['lvl']; $i++) {
                echo " ● ";
            }

            echo " {$node['title']} (#{$node['id']}) \n";
        }
    }
}