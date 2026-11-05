<?php
session_start();
require __DIR__ . '/config.php';

function h($value) {
    return htmlspecialchars(isset($value) ? (string)$value : '', ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (isset($_POST['action']) ? $_POST['action'] : '');

    try {
        if ($action === 'cadastrar') {
            $descricao = trim((string)((isset($_POST['descricao']) ? $_POST['descricao'] : '')));
            $categoria = trim((string)((isset($_POST['categoria']) ? $_POST['categoria'] : '')));
            $compra = filter_var((isset($_POST['valor_compra']) ? $_POST['valor_compra'] : null), FILTER_VALIDATE_FLOAT);
            $venda = filter_var((isset($_POST['valor_venda']) ? $_POST['valor_venda'] : null), FILTER_VALIDATE_FLOAT);
            $estoque = filter_var((isset($_POST['estoque']) ? $_POST['estoque'] : null), FILTER_VALIDATE_INT);

            if ($descricao === '' || $categoria === '') {
                redirectWithMessage('Preencha a descrição e a categoria.', 'error');
            }
            if ($compra === false || $venda === false || $compra < 0 || $venda < 0) {
                redirectWithMessage('Informe valores válidos para compra e venda.', 'error');
            }
            if ($estoque === false || $estoque < 0) {
                redirectWithMessage('Informe uma quantidade de estoque válida.', 'error');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO produtos (descricao, categoria, valor_compra, valor_venda, estoque)
                 VALUES (:descricao, :categoria, :compra, :venda, :estoque)'
            );
            $stmt->execute([
                ':descricao' => $descricao,
                ':categoria' => $categoria,
                ':compra' => $compra,
                ':venda' => $venda,
                ':estoque' => $estoque,
            ]);
            redirectWithMessage('Produto cadastrado com sucesso.');
        }

        if ($action === 'vender') {
            $id = filter_var((isset($_POST['id']) ? $_POST['id'] : null), FILTER_VALIDATE_INT);
            if (!$id) redirectWithMessage('Produto inválido.', 'error');

            // Atualização atômica: impede que o estoque fique negativo.
            $stmt = $pdo->prepare(
                'UPDATE produtos SET estoque = estoque - 1 WHERE id = :id AND estoque > 0'
            );
            $stmt->execute([':id' => $id]);
            redirectWithMessage(
                $stmt->rowCount() ? 'Venda registrada. Estoque atualizado.' : 'Produto não encontrado ou sem estoque.',
                $stmt->rowCount() ? 'success' : 'error'
            );
        }

        if ($action === 'excluir') {
            $id = filter_var((isset($_POST['id']) ? $_POST['id'] : null), FILTER_VALIDATE_INT);
            if (!$id) redirectWithMessage('Produto inválido.', 'error');
            $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
            $stmt->execute([':id' => $id]);
            redirectWithMessage($stmt->rowCount() ? 'Produto excluído.' : 'Produto não encontrado.',
                $stmt->rowCount() ? 'success' : 'error');
        }
    } catch (PDOException $e) {
        redirectWithMessage('Não foi possível concluir a operação. Confira os dados e tente novamente.', 'error');
    }
}

$flash = (isset($_SESSION['flash']) ? $_SESSION['flash'] : null);
unset($_SESSION['flash']);

$busca = trim((string)((isset($_GET['busca']) ? $_GET['busca'] : '')));
if ($busca !== '') {
    $stmt = $pdo->prepare(
        'SELECT * FROM produtos
         WHERE descricao LIKE :busca OR categoria LIKE :busca
         ORDER BY descricao ASC'
    );
    $stmt->execute([':busca' => '%' . $busca . '%']);
    $produtos = $stmt->fetchAll();
} else {
    $produtos = $pdo->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();
}

$resumo = $pdo->query(
    'SELECT COUNT(*) AS total_produtos, COALESCE(SUM(estoque), 0) AS total_itens,
            COALESCE(SUM(valor_venda * estoque), 0) AS valor_estoque
     FROM produtos'
)->fetch();

function moeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Produtos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <header class="topo">
            <div>
                <span class="eyebrow">PAINEL ADMINISTRATIVO</span>
                <h1>Controle de produtos</h1>
                <p>Cadastre produtos, acompanhe o inventário e registre vendas.</p>
            </div>
            <div class="topo-icon" aria-hidden="true">▦</div>
        </header>

        <?php if ($flash): ?>
            <div class="alerta <?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <section class="resumos" aria-label="Resumo do inventário">
            <article class="resumo-card">
                <span>Produtos cadastrados</span>
                <strong><?= (int)$resumo['total_produtos'] ?></strong>
            </article>
            <article class="resumo-card">
                <span>Unidades em estoque</span>
                <strong><?= (int)$resumo['total_itens'] ?></strong>
            </article>
            <article class="resumo-card">
                <span>Valor potencial do estoque</span>
                <strong><?= moeda($resumo['valor_estoque']) ?></strong>
            </article>
        </section>

        <section class="painel">
            <div class="section-heading">
                <div>
                    <span class="section-number">01</span>
                    <h2>Cadastro de produto</h2>
                </div>
                <p>Preencha os campos para adicionar um item ao inventário.</p>
            </div>

            <form method="post" class="formulario">
                <input type="hidden" name="action" value="cadastrar">
                <div class="campo campo-descricao">
                    <label for="descricao">Descrição do produto</label>
                    <input id="descricao" name="descricao" maxlength="120" placeholder="Ex.: Notebook Samsung" required>
                </div>
                <div class="campo">
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Selecione</option>
                        <option>Informática</option>
                        <option>Eletrônicos</option>
                        <option>Escritório</option>
                        <option>Casa</option>
                        <option>Vestuário</option>
                        <option>Alimentos</option>
                        <option>Outros</option>
                    </select>
                </div>
                <div class="campo">
                    <label for="valor_compra">Valor de compra (R$)</label>
                    <input id="valor_compra" name="valor_compra" type="number" min="0" step="0.01" placeholder="0,00" required>
                </div>
                <div class="campo">
                    <label for="valor_venda">Valor de venda (R$)</label>
                    <input id="valor_venda" name="valor_venda" type="number" min="0" step="0.01" placeholder="0,00" required>
                </div>
                <div class="campo">
                    <label for="estoque">Quantidade inicial</label>
                    <input id="estoque" name="estoque" type="number" min="0" step="1" value="0" required>
                </div>
                <div class="acoes-form">
                    <button class="botao primario" type="submit">＋ Cadastrar produto</button>
                    <button class="botao secundario" type="reset">Limpar campos</button>
                </div>
            </form>
        </section>

        <section class="painel inventario">
            <div class="section-heading inventario-heading">
                <div>
                    <span class="section-number">02</span>
                    <h2>Inventário</h2>
                </div>
                <form method="get" class="busca">
                    <label class="visually-hidden" for="busca">Buscar produto</label>
                    <input id="busca" name="busca" value="<?= h($busca) ?>" placeholder="Buscar por descrição ou categoria...">
                    <button type="submit" aria-label="Buscar">⌕</button>
                    <?php if ($busca !== ''): ?><a class="limpar-busca" href="index.php">Limpar</a><?php endif; ?>
                </form>
            </div>

            <div class="tabela-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Compra</th>
                            <th>Venda</th>
                            <th>Lucro unitário</th>
                            <th>Estoque</th>
                            <th class="col-acoes">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$produtos): ?>
                        <tr><td colspan="7" class="vazio">Nenhum produto encontrado. Cadastre um produto acima.</td></tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $produto): ?>
                            <?php $lucro = (float)$produto['valor_venda'] - (float)$produto['valor_compra']; ?>
                            <tr>
                                <td class="produto-nome"><?= h($produto['descricao']) ?></td>
                                <td><span class="categoria-tag"><?= h($produto['categoria']) ?></span></td>
                                <td><?= moeda($produto['valor_compra']) ?></td>
                                <td><?= moeda($produto['valor_venda']) ?></td>
                                <td class="<?= $lucro >= 0 ? 'lucro' : 'prejuizo' ?>"><?= moeda($lucro) ?></td>
                                <td>
                                    <span class="estoque-tag <?= (int)$produto['estoque'] === 0 ? 'sem-estoque' : ((int)$produto['estoque'] <= 3 ? 'baixo-estoque' : '') ?>">
                                        <?= (int)$produto['estoque'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acoes-linha">
                                        <form method="post" onsubmit="return confirm('Registrar a venda de uma unidade deste produto?')">
                                            <input type="hidden" name="action" value="vender">
                                            <input type="hidden" name="id" value="<?= (int)$produto['id'] ?>">
                                            <button class="icone-btn vender" type="submit" title="Vender uma unidade" aria-label="Vender uma unidade" <?= (int)$produto['estoque'] === 0 ? 'disabled' : '' ?>>↗</button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir este produto?')">
                                            <input type="hidden" name="action" value="excluir">
                                            <input type="hidden" name="id" value="<?= (int)$produto['id'] ?>">
                                            <button class="icone-btn excluir" type="submit" title="Excluir produto" aria-label="Excluir produto">×</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="rodape-tabela">
                <span><?= count($produtos) ?> produto(s) nesta visualização</span>
                <span><i class="bolinha"></i> Estoque baixo: 3 unidades ou menos</span>
            </div>
        </section>
        <footer>Controle de Produtos <span>•</span> Sistema de gerenciamento de estoque</footer>
    </main>
</body>
</html>
