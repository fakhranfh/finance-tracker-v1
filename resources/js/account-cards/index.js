import { createAccountCards } from './account-cards.js';
import { routes } from '../routes.js';

createAccountCards({
    ids: {
        root: 'account-cards',
        skeleton: 'account-cards-skeleton',
        list: 'account-cards-list',
        modal: 'account-appearance-modal',
        form: 'account-appearance-form',
    },
    routes: {
        index: routes.accounts.index,
        update: routes.accounts.update,
    },
});
