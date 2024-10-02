<?php

/**
 * 
 * BASIC SQL STATEMENT FUNCTION
 * 
 */
function selectStatement(string $query, array $parameter, $type = 'fetchAll')
{
    global $db;

    $showError = true;
    $output = true;

    try {
        $sqlStatement = $db->prepare($query);
        $result = $sqlStatement->execute($parameter);

        if (!$result) {
            $error = $sqlStatement->errorInfo();
            $errorStatement = $error[2];

            $output = false;

            throw new PDOException($errorStatement);
        } else {
            if ($type == 'fetchAll') {
                $data = $sqlStatement->fetchAll();
            } else if ($type == 'fetch') {
                $data = $sqlStatement->fetch();
            }

            $output = $data;
        }
    } catch (PDOException $e) {
        if ($showError === true) {
            $backtrace = debug_backtrace();
            $errorLine = $backtrace[0]['line'];
?>
            <div class="alert alert-danger" role="alert">
                SQL SELECT ERROR on line <?= $errorLine; ?> : <strong><?= $e->getMessage(); ?></strong>
            </div>
        <?php
        }

        $output = false;
    } finally {
        return $output;
    }
}

function insertStatement(string $query, array $parameter)
{
    global $db;

    $showError = true;
    $output = true;

    try {
        $sqlStatement = $db->prepare($query);
        $result = $sqlStatement->execute($parameter);

        if (!$result) {
            $error = $sqlStatement->errorInfo();
            $errorStatement = $error[2];

            $output = false;

            throw new PDOException($errorStatement);
        } else {
            $output = true;
        }
    } catch (PDOException $e) {

        if ($showError === true) {
            $backtrace = debug_backtrace();
            $errorLine = $backtrace[0]['line'];
        ?>
            <div class="alert alert-danger" role="alert">
                SQL INSERT ERROR on line <?= $errorLine; ?> : <strong><?= $e->getMessage(); ?></strong>
            </div>
        <?php
        }
        $output = false;
    } finally {
        return $output;
    }
}

function updateStatement(string $query, array $parameter)
{
    global $db;

    $showError = true;
    $output = true;

    try {
        $sqlStatement = $db->prepare($query);
        $result = $sqlStatement->execute($parameter);

        if (!$result) {
            $error = $sqlStatement->errorInfo();
            $errorStatement = $error[2];

            $output = false;

            throw new PDOException($errorStatement);
        } else {
            $output = true;
        }
    } catch (PDOException $e) {

        if ($showError === true) {
            $backtrace = debug_backtrace();
            $errorLine = $backtrace[0]['line'];
        ?>
            <div class="alert alert-danger" role="alert">
                SQL UPDATE ERROR on line <?= $errorLine; ?> : <strong><?= $e->getMessage(); ?></strong>
            </div>
        <?php
        }
        $output = false;
    } finally {
        return $output;
    }
}

function statementWrapper(int $DML_MODE, string $query, array $parameter, PDOStatement &$handler = null)
{
    global $db;

    if (!in_array($DML_MODE, [DML_SELECT, DML_SELECT_ALL, DML_INSERT, DML_UPDATE, DML_DELETE], true)) return false;

    $showError = true;
    $output = true;

    if (!in_array($DML_MODE, [DML_SELECT, DML_SELECT_ALL])) {
        $db->beginTransaction();
    }

    try {

        $refHandler = $db->prepare($query);
        $result = $refHandler->execute($parameter);

        if ($result) {

            switch ($DML_MODE) {
                case DML_SELECT:
                    $output = $refHandler->fetch();
                    break;
                case DML_SELECT_ALL:
                    $output = $refHandler->fetchAll();
                    break;
                case DML_INSERT:
                    $output = $result;
                    break;
                case DML_UPDATE:
                    $output = $result;
                    break;
                case DML_DELETE:
                    $output = $result;
                    break;
            }

            if (func_num_args() === 4) {
                $handler = $refHandler;
            }

            if (!in_array($DML_MODE, [DML_SELECT, DML_SELECT_ALL])) {
                $db->commit();
            }
        } else {

            $error = $refHandler->errorInfo();
            $errorStatement = $error[2];

            if (func_num_args() === 4) {
                $handler = $refHandler;
            }

            throw new PDOException($errorStatement);
        }
    } catch (PDOException $e) {

        if (!in_array($DML_MODE, [DML_SELECT, DML_SELECT_ALL])) {
            $db->rollBack();
        }

        if ($showError === true) {
            $backtrace = debug_backtrace();
            $errorLine = $backtrace[0]['line'];
        ?>
            <div class="alert alert-danger" role="alert">
                SQL ERROR on line <?= $errorLine; ?> : <strong><?= $db->errorCode(); ?> <?= $e->getMessage(); ?></strong>
            </div>
<?php
        }

        $output = false;
    } finally {
        return $output;
    }
}

function multiStatementWrapper(int $DML_MODE, array $queries, array $parameters, PDOStatement &$handler = null)
{
    global $db;

    // Validasi mode DML
    if (!in_array($DML_MODE, [DML_SELECT, DML_SELECT_ALL, DML_INSERT, DML_UPDATE, DML_DELETE], true)) {
        return false;
    }

    $showError = true;
    $output = [];
    $db->beginTransaction();  // Selalu mulai transaksi

    try {
        // Loop untuk setiap query
        foreach ($queries as $index => $query) {
            // Persiapkan query
            $refHandler = $db->prepare($query);
            
            // Eksekusi query dengan parameter yang sesuai
            $result = $refHandler->execute($parameters[$index]);

            if ($result) {
                // Tangani hasil berdasarkan jenis DML
                switch ($DML_MODE) {
                    case DML_SELECT:
                        $output[] = $refHandler->fetch();
                        break;
                    case DML_SELECT_ALL:
                        $output[] = $refHandler->fetchAll();
                        break;
                    case DML_INSERT:
                    case DML_UPDATE:
                    case DML_DELETE:
                        $output[] = $result;
                        break;
                }

                // Jika handler diinginkan (jika diberikan argumen ke-4)
                if (func_num_args() === 4) {
                    $handler = $refHandler;
                }
            } else {
                // Jika terjadi error, rollback dan lempar exception
                $error = $refHandler->errorInfo();
                $errorStatement = $error[2];

                if (func_num_args() === 4) {
                    $handler = $refHandler;
                }

                throw new PDOException($errorStatement);
            }
        }

        // Commit transaksi setelah semua query sukses
        $db->commit();
    } catch (PDOException $e) {
        // Rollback transaksi jika ada error
        $db->rollBack();

        // Tampilkan error jika $showError true
        if ($showError === true) {
            $backtrace = debug_backtrace();
            $errorLine = $backtrace[0]['line'];
            ?>
            <div class="alert alert-danger" role="alert">
                SQL ERROR on line <?= $errorLine; ?> : <strong><?= $db->errorCode(); ?> <?= $e->getMessage(); ?></strong>
            </div>
            <?php
        }

        $output = false;
    } finally {
        return $output;
    }
}
