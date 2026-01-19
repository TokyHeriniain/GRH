import '../../css/app.css';
import toast from 'react-hot-toast';
const Unauthorized = () => {
    return (
        <div className="container mt-5 text-center">
            <h1 className="display-4 text-danger">Accès refusé</h1>
            <p className="lead">Vous n'avez pas les permissions pour accéder à cette page.</p>
        </div>
    );
};
export default Unauthorized;