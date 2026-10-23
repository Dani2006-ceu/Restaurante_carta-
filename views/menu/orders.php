<?php
use App\Models\Order;

$currency = (string) $restaurant['currency'];
?>
<div class="panel" data-pedidos-pagina>
    <header class="panel-head">
        <div>
            <p class="eyebrow">Pedidos recibidos</p>
            <h1>Pedidos</h1>
            <p class="muted">Los pedidos nuevos aparecen solos; esta página se actualiza cuando llega uno.</p>
        </div>
        <a class="button button-secondary" href="index.php?route=menu">Mi menú</a>
    </header>

    <section class="card panel-card sonido-card" aria-labelledby="sonido-titulo">
        <h2 id="sonido-titulo">Sonido de pedidos</h2>
        <p class="muted">Elige cómo suena el aviso cuando llega un pedido. Se guarda en este dispositivo.</p>
        <fieldset class="sonidos">
            <legend class="sr-only">Tipo de sonido</legend>
            <?php foreach ([
                'timbre' => 'Timbre de mostrador',
                'campana' => 'Campana',
                'cocina' => 'Campanita de cocina',
                'marimba' => 'Marimba',
                'suave' => 'Suave',
                'pitido' => 'Pitido',
            ] as $sound => $label): ?>
                <label>
                    <input type="radio" name="sonido_tipo" value="<?= h($sound) ?>" data-sonido-tipo>
                    <span><?= h($label) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <div class="sonido-controles">
            <label class="volumen" for="sonido-volumen">
                Volumen
                <input id="sonido-volumen" type="range" min="10" max="100" step="5" data-sonido-volumen>
            </label>
            <label class="check">
                <input type="checkbox" data-sonido-activo> Sonido activado
            </label>
            <button class="button button-secondary button-small" type="button" data-sonido-probar>▶ Probar</button>
        </div>
    </section>

    <div class="pedidos-lista" data-pedidos-lista>
    <?php if ($orders === []): ?>
        <section class="card panel-card">
            <p class="muted">Aún no hay pedidos. Comparte el link o el QR de tu menú para empezar a recibirlos.</p>
        </section>
    <?php endif; ?>

    <div class="orders-grid">
        <?php foreach ($orders as $order): ?>
            <?php $status = (string) $order['status']; ?>
            <article class="card order-card status-<?= h($status) ?>">
                <header class="order-head">
                    <div>
                        <strong>#<?= (int) $order['id'] ?> · <?= h((string) $order['customer_name']) ?></strong>
                        <small>
                            <?= h(Order::TYPES[$order['order_type']] ?? (string) $order['order_type']) ?>
                            <?php if ($order['table_label'] !== null): ?> · <?= h((string) $order['table_label']) ?><?php endif; ?>
                        </small>
                        <small><time datetime="<?= h((string) $order['created_at']) ?>Z" data-hora-local><?= h((string) $order['created_at']) ?> UTC</time></small>
                    </div>
                    <span class="status-pill"><?= h(Order::STATUSES[$status] ?? $status) ?></span>
                </header>

                <ul class="order-lines">
                    <?php foreach ($order['lines'] as $line): ?>
                        <li>
                            <span><?= (int) $line['quantity'] ?> × <?= h((string) $line['name']) ?></span>
                            <span><?= h(money((float) $line['unit_price'] * (int) $line['quantity'], $currency)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($order['note'] !== null): ?>
                    <p class="order-note"><?= h((string) $order['note']) ?></p>
                <?php endif; ?>
                <?php if ($order['customer_phone'] !== null): ?>
                    <p class="order-phone">Tel: <a href="tel:<?= h((string) $order['customer_phone']) ?>"><?= h((string) $order['customer_phone']) ?></a></p>
                <?php endif; ?>
                <p class="order-total">Total <strong><?= h(money((float) $order['total'], $currency)) ?></strong></p>

                <form class="order-status" method="post" action="index.php?route=pedido-estado">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <?php foreach (Order::STATUSES as $option => $label): ?>
                        <?php if ($option !== $status): ?>
                            <button class="button button-secondary" type="submit" name="status" value="<?= h($option) ?>">
                                <?= h($label) ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
    </div>
</div>
