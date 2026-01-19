import React from "react";

const PersonnelFilters = ({ filters = {}, setFilters = () => {} }) => {
    const safeValue = (value) => value ?? ""; // pour éviter undefined

    return (
        <div className="mb-3 d-flex gap-3 align-items-end flex-wrap">
            <div>
                <label className="form-label">Nom / Prénom</label>
                <input
                    type="text"
                    className="form-control"
                    value={safeValue(filters.nomPrenom)}
                    onChange={(e) =>
                        setFilters({ ...filters, nomPrenom: e.target.value })
                    }
                />
            </div>
            <div>
                <label className="form-label">Fonction</label>
                <input
                    type="text"
                    className="form-control"
                    value={safeValue(filters.fonction)}
                    onChange={(e) =>
                        setFilters({ ...filters, fonction: e.target.value })
                    }
                />
            </div>
            <div>
                <label className="form-label">Numéro Matricule</label>
                <input
                    type="text"
                    className="form-control"
                    value={safeValue(filters.matricule)}
                    onChange={(e) =>
                        setFilters({ ...filters, matricule: e.target.value })
                    }
                />
            </div>
        </div>
    );
};

export default PersonnelFilters;
