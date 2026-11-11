import './styles/admin_import.scss';

import { Application } from '@hotwired/stimulus';
import ImportUsersController from './controllers/import_users_controller';

const application = Application.start();
application.register('import-users', ImportUsersController);
