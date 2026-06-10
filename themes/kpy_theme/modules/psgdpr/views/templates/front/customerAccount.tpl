{assign "title" {l s='Stored personal data' d='Shop.Theme.Customeraccount'}}
{assign "subtitle" ""}

{include file="components/account-grid-item.tpl"
    link=$front_controller
    title=$title
    subtitle=$subtitle
    iconType='tpl'
    iconValue='components/svg-privacy.tpl'
}