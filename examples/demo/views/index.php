<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Payment Gateway PHP SDK Demo</title>
    <link rel="stylesheet" href="/assets/demo.css"/>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div>
            <p class="eyebrow">PHP SDK Demo Console</p>
            <h1>Payment Gateway API 联调控制台</h1>
            <p>按 API 文档分组展示接口，自动生成沙盒参数，并使用当前 PHP SDK 发起真实 OpenAPI 调用。</p>
        </div>
        <div class="env-panel">
            <span><?= $config->isLivemode() ? 'LIVE' : 'SANDBOX' ?></span>
            <strong><?= h($config->getMerchantNo()) ?></strong>
            <small><?= h($config->getBaseUrl()) ?></small>
        </div>
    </header>

    <section class="api-map">
        <?php foreach ($groups as $group): ?>
            <article class="group-panel">
                <div class="group-head">
                    <h2><?= h($group['name']) ?></h2>
                    <p><?= h($group['description']) ?></p>
                </div>
                <div class="api-list">
                    <?php foreach ($group['apis'] as $api): ?>
                        <a class="api-row" href="/demo/apis/<?= h($api['code']) ?>">
                            <span>
                                <strong><?= h($api['name']) ?></strong>
                                <small><?= h($api['description']) ?></small>
                            </span>
                            <em><?= h($api['method']) ?></em>
                        </a>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>

