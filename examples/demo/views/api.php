<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title><?= h($definition['name']) ?> - Payment Gateway PHP SDK Demo</title>
    <link rel="stylesheet" href="/assets/demo.css"/>
</head>
<body>
<main class="shell">
    <nav class="crumb">
        <a href="/demo/apis">API 首页</a>
        <span>/</span>
        <span><?= h($definition['groupName']) ?></span>
    </nav>

    <header class="api-title">
        <div>
            <p class="eyebrow"><?= h($definition['method'] . ' ' . $definition['path']) ?></p>
            <h1><?= h($definition['name']) ?></h1>
            <p><?= h($definition['description']) ?></p>
        </div>
        <div class="env-panel">
            <span><?= $config->isLivemode() ? 'LIVE' : 'SANDBOX' ?></span>
            <strong><?= h($config->getMerchantNo()) ?></strong>
            <small><?= h($config->getBaseUrl()) ?></small>
        </div>
    </header>

    <section class="workbench">
        <form class="request-form" method="post" action="/demo/apis/<?= h($definition['code']) ?>">
            <div class="section-head">
                <h2>请求参数</h2>
                <p>默认值来自 SDK 示例和 config/merchant-config.php，可直接修改后提交。</p>
            </div>

            <div class="field-grid">
                <?php foreach ($definition['requestFields'] as $field): ?>
                    <?php $name = $field['name']; $value = $params[$name] ?? ''; ?>
                    <div class="field" data-field-name="<?= h($name) ?>">
                        <span class="label-line">
                            <strong><?= h($field['label']) ?></strong>
                            <?php if (!empty($field['required'])): ?><em>必填</em><?php endif; ?>
                        </span>
                        <?php if ($field['type'] === 'select'): ?>
                            <select name="params[<?= h($name) ?>]">
                                <?php foreach ($field['options'] as $option): ?>
                                    <option value="<?= h($option['value']) ?>" <?= (string)$option['value'] === (string)$value ? 'selected' : '' ?>>
                                        <?= h($option['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['type'] === 'textarea'): ?>
                            <textarea name="params[<?= h($name) ?>]" placeholder="<?= h($field['placeholder']) ?>"><?= h($value) ?></textarea>
                        <?php else: ?>
                            <input name="params[<?= h($name) ?>]"
                                   value="<?= h($value) ?>"
                                   placeholder="<?= h($field['placeholder']) ?>"
                                   <?= $name === 'merchantNo' ? 'readonly' : '' ?>/>
                        <?php endif; ?>
                        <small><?= h($field['description']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="primary-action" type="submit"><?= h($definition['actionLabel']) ?></button>
        </form>

        <aside class="response-guide">
            <div class="section-head">
                <h2>响应字段</h2>
                <p>提交后会在下方展示完整响应 JSON，这里说明关键字段含义。</p>
            </div>
            <dl>
                <?php foreach ($definition['responseFields'] as $field): ?>
                    <div>
                        <dt><?= h($field['label']) ?></dt>
                        <dd><?= h($field['description']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </aside>
    </section>

    <?php if ($invocation !== null): ?>
        <section class="result-panel">
            <div class="result-head <?= $invocation['success'] ? 'ok' : 'fail' ?>">
                <h2><?= h($invocation['summary']) ?></h2>
                <span><?= $invocation['success'] ? 'SUCCESS' : 'ERROR' ?></span>
            </div>

            <div class="json-pair">
                <article>
                    <h3>请求明文</h3>
                    <pre><?= h($invocation['requestJson']) ?></pre>
                </article>
                <?php if ($invocation['success']): ?>
                    <article>
                        <h3>响应参数</h3>
                        <pre><?= h($invocation['responseJson']) ?></pre>
                    </article>
                <?php else: ?>
                    <article>
                        <h3>错误信息</h3>
                        <pre><?= h($invocation['errorMessage']) ?></pre>
                    </article>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<script>
window.paymentMethodDataExamples = <?= json_encode(DemoApiCatalog::paymentMethodDataExamples(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/assets/demo.js"></script>
</body>
</html>
