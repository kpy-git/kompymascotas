/**
 * This file is part of the Gamifications module.
 *
 * @author    Sarunas Jonusas, <jonusas.sarunas@gmail.com>
 * @copyright Copyright (c) permanent, Sarunas Jonusas
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(function() {
    var referralUrl = document.querySelector('.js-gamifications-referral-url-copy');

    referralUrl.addEventListener('click', function(event) {
        // event.preventDefault();

        var input = document.querySelector('.js-gamifications-referral-url-input');
        
        var range = document.createRange();
        range.selectNodeContents(input);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);

        // input.select();

        try {
            document.execCommand('copy');
        } catch (error) {
            console.warn('Your browser does not support `copy` command.');
        }
    });

})();
