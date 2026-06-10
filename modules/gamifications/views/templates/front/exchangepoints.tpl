{extends file='customer/page.tpl'}


{block name='page_title'}
    <img src="{$module_img}earn.svg" alt="acumula puntos" class="icon-title"> {l s='Mis puntos' mod='gamifications'}
{/block}

{block name="page_content"}
    {if !$modules.kpyaccountverify.account_verified }
        {widget name='kpyaccountverify' message={l s='To use your loyalty points, you must verify your account.' mod='gamifications'}}

    {else}

        <p><strong>{l s='Purchase and accumulate points' mod='gamifications'}</strong>. {l s='Earn 1 point for every €1 you spend when purchasing any of our products.' mod='gamifications'}</p>

        <div class="customer-points">
            <img src="{$urls.img_url}loyalty.svg" alt="{l s='Points' mod='gamifications'}">
            <h4>{l s='Available points' mod='gamifications'}</h4>
            <span class="points">{$gamifications_customer.total_points}</span>
        </div>


        {if isset($point_exchange_rewards) && !empty($point_exchange_rewards)}
            <h4 class="mt-3"><span><img src="{$module_img}gift.svg" alt="recompensas" class="icon-title"></span>{l s='Avaialble rewards' mod='gamifications'}</h4>
            <p>{l s='Use your points to get discount vouchers and free products on your next purchases.' mod='gamifications'}</p>

            <div class="rewards">

                {foreach from=$point_exchange_rewards key=key item=reward}
                    {if $reward.points <= $gamifications_customer.total_points}
                        {assign var="buttonStatus" value="enabled"}
                    {else}
                        {assign var="buttonStatus" value="disabled"}
                    {/if}

                    {capture name='progress' assign="percentage_progress"}
                        {math equation="min(max_points, customer_points * max_points / reward_points)"
                            max_points=100
                            customer_points=$gamifications_customer.total_points
                            reward_points=$reward.points
                            format="%d"
                        }
                    {/capture}

                    {assign var="code_label" value={l s='%d points' sprintf=[$reward.points] mod='gamifications'}}

                    {if isset($reward.image_link)}
                        {assign var='reward_value' value=$reward.name}
                        {assign var='reward_class' value='reward-gift'}
                        {assign var='reward_image' value=$reward.image_link}
                    {else}
                        {assign var='reward_value' value=$reward.discount_value|string_format:"%d €"}
                        {assign var='reward_image' value=''}

                        {if $reward.discount_value >= 20}
                            {assign var='reward_class' value='reward-gold'}
                        {elseif $reward.discount_value >= 10}
                            {assign var='reward_class' value='reward-silver'}
                        {else}
                            {assign var='reward_class' value='reward-bronze'}
                        {/if}
                    {/if}

                    {include file="module:gamifications/views/templates/front/_partials/reward.tpl"
                        code=$code_label
                        name=$reward.type
                        value=$reward_value
                        buttonStatus=$buttonStatus
                        csrf_token=$csrf_token
                        form_value=$reward.id_gamifications_point_exchange
                        percentage=$percentage_progress|trim
                        reward_points=$reward.points
                        customer_points=$gamifications_customer.total_points
                        classes=$reward_class
                        reward_image=$reward_image
                    }

                {/foreach}

            </div>
        {else}
            <article class="alert alert-info" role="alert" data-alert="info">
                <ul>
                    <li>{l s='Theres no rewards at the moment, please check back soon!' mod='gamifications'}</li>
                </ul>
            </article>
        {/if}

    {/if}
{/block}
