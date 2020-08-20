{*
*  Copyright (C) Lk Interactive - All Rights Reserved.
*
*  This is proprietary software therefore it cannot be distributed or reselled.
*  Unauthorized copying of this file, via any medium is strictly prohibited.
*  Proprietary and confidential.
*
* @author    Lk Interactive <contact@lk-interactive.fr>
* @copyright 2020.
* @license   Commercial license
*}

{if isset($success_import)}
  <div class="alert alert-success" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
    <p class="alert-text">{l s='Import is successfully passed. See the log below to get details' d='Modules.Lkebp.Admin'}</p>
  </div>
{/if}
{if isset($smarty.get.importnotcontinue)}
  <div class="alert alert-danger" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
    <p class="alert-text">{l s='You haave to disable the shop before start import' d='Modules.Lkebp.Admin'}</p>
  </div>
{/if}
{if isset($error_import)}
  <div class="alert alert-info" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
    {$error_import}
  </div>
{/if}
<div class="panel">
  <div class="col-xs-3">
    <ul class="nav nav-pills nav-stacked">
      <li class="active"><a href="#import" data-toggle="tab"><i
                  class="icon process-icon-import"></i> {l s='Import area' d='Modules.Lkebp.Admin'}</a></li>
      <li><a href="#logs" data-toggle="tab"><i class="icon process-icon-stats"></i> {l s='Logs' d='Modules.Lkebp.Admin'}
        </a></li>
      <li><a href="#cron" data-toggle="tab"><i class="icon icon-clock-o"></i> {l s='Cron link' d='Modules.Lkebp.Admin'}
        </a></li>
    </ul>
  </div>
  <div class="col-xs-9">
    <!-- Tab panes -->
    <div class="tab-content">
      <div class="tab-pane active" id="import">
        <div class="alert alert-warning" role="alert">
          <p class="alert-text">{l s='Carefull before start any import. Be sure site mmaintenance is on' d='Modules.Lkebp.Admin'}</p>
        </div>
        <form action="{$import_form|escape:'htmlall':'UTF-8'}" method="post" id="lkebp-start-import">
          <ul>
            <li style="float: left; width: 250px; margin-bottom: 15px">
              <label for="category">
                <input class="check-button" {if $shop_enable}disabled{/if} type="checkbox" name="entity[]" id="category"
                       value="import_category">
                {l s='Import category' d='Modules.Lkebp.Admin'}
              </label>
            </li>
            <li style="float: left; width: 250px; margin-bottom: 15px">
              <label for="product">
                <input class="check-button" {if $shop_enable}disabled{/if} type="checkbox" name="entity[]" id="product"
                       value="import_product">
                {l s='Import product' d='Modules.Lkebp.Admin'}
              </label>
            </li>
            <li style="float: left; width: 250px; margin-bottom: 15px">
              <label for="combination">
                <input class="check-button" {if $shop_enable}disabled{/if} type="checkbox" name="entity[]"
                       id="combination" value="import_combination">
                {l s='Import product' d='Modules.Lkebp.Admin'}
              </label>
            </li>
          </ul>
          <div class="clearfix"></div>
          <div class="text-center">
            <input type="submit" class="btn btn-default" {if $shop_enable}disabled{/if} name="SubmitImportEntity"
                   value="{l s='Start import' d='Admin.Actions'}"/>
          </div>
        </form>
      </div>
      <div class="tab-pane" id="logs">
        <div class="alert alert-info" role="alert">
          {foreach from=$logs key=k item=log}
            <div class="log-item" style="margin-bottom: 25px;">
              <h4><strong style="text-decoration: underline">{l s='IMPORT' d='Modules.Lkebp.Admin'} #{$k} :</strong>
              </h4>
              <strong style="margin-bottom: 5px;display: block;">{$log['Date'][0]} / {$log['Date'][1]}</strong>
              {if (isset($log['infos']))}
                {foreach from=$log['infos'] item=info}
                  {$info}
                  <br/>
                {/foreach}
              {/if}
            </div>
          {/foreach}
        </div>
      </div>
      <div class="tab-pane" id="cron">
        <div class="alert alert-success" role="alert">
          <h3>{l s='URL cron' d='Admin.Actions'}</h3>
        </div>
        <ul>
          {foreach from=$cron_url key=k item=url}
            <li><strong>{$k} : </strong>{$url}</li>
          {/foreach}
        </ul>
      </div>
    </div>
  </div>
  <div class="clearfix"></div>
</div>
<div class="clearfix"></div>
