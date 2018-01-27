<?php
if (IN_MANAGER_MODE != "true") {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_template')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$reset = isset($_POST['reset']) && $_POST['reset'] == 'true' ? 1 : 0;

$tbl_site_templates = $modx->getFullTableName('site_templates');
$tbl_site_tmplvar_templates = $modx->getFullTableName('site_tmplvar_templates');
$tbl_site_tmplvars = $modx->getFullTableName('site_tmplvars');

$siteURL = $modx->config['site_url'];

$updateMsg = '';
$templatename = '';

if (isset($_POST['listSubmitted'])) {
    $updateMsg .= '<div class="text-success" id="updated">' . $_lang['sort_updated'] . '</div>';
    foreach ($_POST as $listName => $listValue) {
        if ($listName == 'listSubmitted' || $listName == 'reset') {
            continue;
        }
        $orderArray = explode(';', rtrim($listValue, ';'));
        foreach ($orderArray as $key => $item) {
            if (strlen($item) == 0) {
                continue;
            }
            $key = $reset ? 0 : $key;
            $tmplvar = ltrim($item, 'item_');
            $modx->db->update(array('rank' => $key), $tbl_site_tmplvar_templates, "tmplvarid='{$tmplvar}' AND templateid='{$id}'");
        }
    }
    // empty cache
    $modx->clearCache('full');
}

$rs = $modx->db->select("tv.name AS name, tv.caption AS caption, tv.id AS id, tr.templateid, tr.rank, tm.templatename", "{$tbl_site_tmplvar_templates} AS tr
		INNER JOIN {$tbl_site_tmplvars} AS tv ON tv.id = tr.tmplvarid
		INNER JOIN {$tbl_site_templates} AS tm ON tr.templateid = tm.id", "tr.templateid='{$id}'", "tr.rank ASC, tv.rank ASC, tv.id ASC");

if ($modx->db->getRecordCount($rs)) {
    $sortableList = '<div class="clearfix"><ul id="sortlist" class="sortableList">';
    while ($row = $modx->db->getRow($rs)) {
        $templatename = $row['templatename'];
        $caption = $row['caption'] != '' ? $row['caption'] : $row['name'];
        $sortableList .= '<li id="item_' . $row['id'] . '"><i class="fa fa-list-alt"></i> ' . $caption . ' <small class="protectedNode" style="float:right">[*' . $row['name'] . '*]</small></li>';
    }
    $sortableList .= '</ul></div>';
} else {
    $updateMsg = '<p class="text-danger">' . $_lang['tmplvars_novars'] . '</p>';
}
?>

<script type="text/javascript">

    var actions = {
        save: function() {
            var el = document.getElementById('updated');
            if (el) {
                el.style.display = 'none';
            }
            el = document.getElementById('updating');
            if (el) {
                el.style.display = 'block';
            }
            setTimeout('document.sortableListForm.submit()', 1000);
        }, cancel: function() {
            window.location.href = 'index.php?a=16&amp;id=<?= $id ?>';
        },
    };

    function renderList()
    {
        var list = '';
        var els = document.querySelectorAll('.sortableList > li');
        for (var i = 0; i < els.length; i++) {
            list += els[i].id + ';';
        }
        document.getElementById('list').value = list;
    }

    var sortdir = 'asc';

    function sort()
    {
        var els = document.querySelectorAll('.sortableList > li');
        var keyA, keyB;
        if (sortdir === 'asc') {
            els = [].slice.call(els).sort(function(a, b) {
                keyA = a.innerText.toLowerCase();
                keyB = b.innerText.toLowerCase();
                return keyA.localeCompare(keyB);
            });
            sortdir = 'desc';
        } else {
            els = [].slice.call(els).sort(function(b, a) {
                keyA = a.innerText.toLowerCase();
                keyB = b.innerText.toLowerCase();
                return keyA.localeCompare(keyB);
            });
            sortdir = 'asc';
        }
        var ul = document.getElementById('sortlist');
        var list = '';
        for (var i = 0; i < els.length; i++) {
            ul.appendChild(els[i]);
            list += els[i].id + ';';
        }
        document.getElementById('list').value = list;
    }

    function resetSortOrder()
    {
        if (confirm('<?= $_lang["confirm_reset_sort_order"] ?>') === true) {
            documentDirty = false;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reset';
            input.value = 'true';
            document.sortableListForm.appendChild(input);
            actions.save();
        }
    }
</script>

<h1>
    <i class="fa fa-sort-numeric-asc"></i><?= ($templatename ? $templatename . '<small>(' . $id . ')</small>' : $_lang['template_tv_edit_title']) ?>
</h1>

<?= $_style['actionbuttons']['dynamic']['save'] ?>

<div class="tab-page">
    <div class="container container-body">
        <?php
        if ($sortableList) {
        ?>
        <b><?= $_lang['template_tv_edit'] ?></b>
        <p><?= $_lang["tmplvars_rank_edit_message"] ?></p>
        <p>
            <a class="btn btn-secondary" href="javascript:;" onclick="sort();return false;"><i class="<?= $_style['actions_sort'] ?>"></i> <?= $_lang['sort_alphabetically'] ?></a>
            <a class="btn btn-secondary" href="javascript:;" onclick="resetSortOrder();return false;"><i class="<?= $_style['actions_refresh'] ?>"></i> <?= $_lang['reset_sort_order'] ?></a>
        </p>
        <?= $updateMsg ?>
        <span class="text-danger" style="display:none;" id="updating"><?= $_lang['sort_updating'] ?></span>
        <?= $sortableList ?>
        <?php
        } else {
            echo $updateMsg;
        }
        ?>
    </div>
</div>

<form action="" method="post" name="sortableListForm">
    <input type="hidden" name="listSubmitted" value="true" />
    <input type="hidden" id="list" name="list" value="" />
</form>

<script type="text/javascript">

    evo.sortable('.sortableList > li', {
        complete: function() {
            renderList();
        }
    })

</script>
