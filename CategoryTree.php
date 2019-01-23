<?php

require 'PDOSingleton.php';

class CategoryTree
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = PDOSingleton::getInstance();
    }

    public function add(string $title, int $parentNode = null)
    {
        $nodeData = $this->getNodeData($parentNode);
        $rightKey = $nodeData['rgt'];
        $level = $nodeData['lvl'];

        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE `category` SET `rgt` = `rgt` + 2, `lft` = IF(`lft` > :rgt, `lft` + 2, `lft`) "
                . " WHERE `rgt` >= :rgt";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "rgt" => $rightKey
            ]);

            $sql = "INSERT INTO `category` SET `title` = :title, `lft` = :rgt, `rgt` = :rgt + 1, `lvl` = :lvl + 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "title" => $title,
                "rgt" => $rightKey,
                "lvl" => $level
            ]);

            $insertedId = $this->pdo->lastInsertId();

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception($e->getMessage());
        }

        return $insertedId;
    }

    public function delete(int $nodeId)
    {
        $nodeData = $this->getNodeData($nodeId);
        $leftKey = $nodeData['lft'];
        $rightKey = $nodeData['rgt'];

        try {
            $this->pdo->beginTransaction();

            $sql = "DELETE FROM `category` WHERE `lft` >= :lft AND `rgt` <= :rgt";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "lft" => $leftKey,
                "rgt" => $rightKey
            ]);

            $sql = "UPDATE `category` SET `lft` = IF(`lft` > :lft, `lft` - (:rgt - :lft + 1), `lft`), `rgt` = `rgt` - (:rgt - :lft + 1) WHERE `rgt` > :rgt";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "lft" => $leftKey,
                "rgt" => $rightKey
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function rename(int $id, string $title)
    {
        $sql = "UPDATE `category` SET `title` = :title WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "id" => $id,
            "title" => $title
        ]);
    }

    public function moveNodeUp(int $id)
    {
        $nodeData = $this->getNodeData($id);
        $parentNodeData = $this->getParentNodeData($nodeData);

        if (!$parentNodeData) {
            throw new Exception("It's root node. Moving up is impossible");
        }

        if ($parentNodeData['lvl'] == 0) {
            throw new Exception("It's maximum level. Moving up is impossible");
        }

        $skewTree =  $nodeData['rgt'] - $nodeData['lft'] + 1;
        $newParentNodeRgt = $parentNodeData['rgt'] - $skewTree;
        $skewEdit = $newParentNodeRgt - $nodeData['lft'] + 1;

        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE `category` SET `rgt` = :parentNodeNewRgt WHERE `id` = :parentId";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "parentNodeNewRgt" => $newParentNodeRgt,
                "parentId" => $parentNodeData['id']
            ]);

            $sql = "UPDATE `category` SET `lft` = (`lft` * -1), `rgt` = (`rgt` * -1) WHERE `lft` >= :lft AND `rgt` <= :rgt";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "lft" => $nodeData['lft'],
                "rgt" => $nodeData['rgt']
            ]);

            $sql = "UPDATE `category` SET `lft` = `lft` - :skew_tree, `rgt` = `rgt` - :skew_tree WHERE "
                ."`lft` > :rgt AND `rgt` < :parent_right";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "skew_tree" => $skewTree,
                "rgt" => $nodeData['rgt'],
                "parent_right" => $parentNodeData['rgt']
            ]);

            $sql = "UPDATE `category` SET `lft` = (`lft` * -1) + :skew_edit, `rgt` = (`rgt` * -1) + :skew_edit, "
                ."`lvl` = `lvl` - 1 WHERE  `lft` < 0 AND `rgt` < 0";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "skew_edit" => $skewEdit,
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function moveNodeDown(int $id)
    {
        $nodeData = $this->getNodeData($id);
        $parentNodeData = $this->getParentNodeData($nodeData);
        $neighborNodeData = $this->getNeighborNodeData($nodeData);

        if (!$neighborNodeData) {
            throw new Exception("There's no correct place for moving node down");
        }

        $skewTree =  $nodeData['rgt'] - $nodeData['lft'] + 1;
        $skewEdit = $neighborNodeData['rgt'] - $skewTree - $nodeData['lft'];

        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE `category` SET `lft` = (`lft` * -1), `rgt` = (`rgt` * -1) WHERE `lft` >= :lft AND `rgt` <= :rgt";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                "lft" => $nodeData['lft'],
                "rgt" => $nodeData['rgt']
            ]);

            if ($neighborNodeData['lft'] > $nodeData['rgt']) {
                $sql = "UPDATE `category` SET `lft` = `lft` - :skew_tree, `rgt` = IF(`id` <> :neighbor_id, `rgt` - :skew_tree, `rgt`) "
                    ."WHERE `lft` > :rgt AND `rgt` < :parent_right";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    "skew_tree" => $skewTree,
                    "rgt" => $nodeData['rgt'],
                    "parent_right" => $parentNodeData['rgt'],
                    "neighbor_id" => $neighborNodeData['id']
                ]);

                $sql = "UPDATE `category` SET `lft` = (`lft` * -1) + :skew_edit, `rgt` = (`rgt` * -1) + :skew_edit, "
                    ."`lvl` = `lvl` + 1 WHERE  `lft` < 0 AND `rgt` < 0";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    "skew_edit" => $skewEdit,
                ]);
            } else {
                $sql = "UPDATE `category` SET `rgt` = `rgt` + :skew_tree WHERE `id` = :neighbor_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    "skew_tree" => $skewTree,
                    "neighbor_id" => $neighborNodeData['id']
                ]);

                $sql = "UPDATE `category` SET `lft` = (`lft` * -1) - 1, `rgt` = (`rgt` * -1) -1, "
                    ."`lvl` = `lvl` + 1 WHERE  `lft` < 0 AND `rgt` < 0";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    "skew_edit" => $skewEdit,
                ]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    protected function getNodeData(int $nodeId = null)
    {
        if ($nodeId) {
            $stmt = $this->pdo->prepare("SELECT * FROM `category` WHERE `id` = ?");
            $stmt->execute([$nodeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception("node with id #{$nodeId} is not found");
            }
        } else {
            $result = $this->pdo->query("SELECT * FROM `category` ORDER BY `rgt` DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                $result = $this->createRootNode();
            }
        }


        return $result;
    }

    protected function getParentNodeData(array $nodeData)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `category` WHERE `lft` < :lft AND `rgt` > :rgt ORDER BY `lft` DESC LIMIT 1");
        $stmt->execute([
            'lft' => $nodeData['lft'],
            'rgt' => $nodeData['rgt'],
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result;
    }


    protected function getNeighborNodeData(array $nodeData)
    {
        $parentNodeData = $this->getParentNodeData($nodeData);

        $sql = "SELECT * FROM `category` WHERE `lft` > :parent_lft AND `rgt` < :parent_rgt "
            . "AND `lvl` = :lvl AND `id` <> :moving_node_id ORDER BY `lft` DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'parent_lft' => $parentNodeData['lft'],
            'parent_rgt' => $parentNodeData['rgt'],
            'lvl' => $nodeData['lvl'],
            'moving_node_id' => $nodeData['id']
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    protected function createRootNode()
    {
        $params = [
            'title' => 'root',
            'lft' => 1,
            'rgt' => 2,
            'lvl' => 0
        ];

        $this->pdo->exec("INSERT INTO `category` SET `title` = '{$params['title']}', "
            . "`lft` = {$params['lft']}, `rgt` = {$params['rgt']}, `lvl` = {$params['lvl']}");

        $params['id'] = $this->pdo->lastInsertId();

        return $params;
    }

    public function getAll()
    {
        return $this->pdo->query("SELECT * FROM `category` ORDER BY `lft`")->fetchAll(PDO::FETCH_ASSOC);
    }

}