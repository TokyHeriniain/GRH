import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import axios from 'axios';

const PersonnelDocuments = () => {
  const { id } = useParams();
  const [documents, setDocuments] = useState([]);
  const [nom, setNom] = useState('');
  const [type, setType] = useState('');
  const [fichier, setFichier] = useState(null);
  const [sortBy, setSortBy] = useState('date'); // 'name' ou 'date'
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDocuments();
  }, [id]);

  const loadDocuments = async () => {
    try {
      const res = await axios.get(`/api/personnels/${id}/documents`);
      setDocuments(res.data);
    } catch {
      alert("Erreur lors du chargement");
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!fichier) return alert("Sélectionnez un fichier");

    const formData = new FormData();
    formData.append('nom', nom);
    formData.append('type', type);
    formData.append('fichier', fichier);

    try {
      const res = await axios.post(`/api/personnels/${id}/documents`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      setDocuments([...documents, res.data]);
      setNom('');
      setType('');
      setFichier(null);
      document.getElementById('fileInput').value = '';
    } catch {
      alert("Erreur lors de l'envoi");
    }
  };

  const handleDelete = async (docId) => {
    if (!window.confirm("Supprimer ce document ?")) return;
    try {
      await axios.delete(`/api/documents/${docId}`);
      setDocuments(documents.filter((d) => d.id !== docId));
    } catch {
      alert("Erreur lors de la suppression");
    }
  };

  const sortedDocuments = [...documents].sort((a, b) => {
    if (sortBy === 'name') return (a.nom || '').localeCompare(b.nom || '');
    return new Date(b.created_at) - new Date(a.created_at);
  });

  const isImage = (fileName) => /\.(jpg|jpeg|png|webp)$/i.test(fileName);
  const isPDF = (fileName) => /\.pdf$/i.test(fileName);

  if (loading) return <p>Chargement...</p>;

  return (
    <div>
      <h4>📎 Gestion des documents</h4>
      <form onSubmit={handleSubmit} className="mb-4">
        <input className="form-control mb-2" placeholder="Nom" value={nom} onChange={e => setNom(e.target.value)} />
        <input className="form-control mb-2" placeholder="Type (ex: contrat, diplôme...)" value={type} onChange={e => setType(e.target.value)} />
        <input id="fileInput" type="file" className="form-control mb-2" onChange={e => setFichier(e.target.files[0])} required />
        <button type="submit" className="btn btn-primary">Ajouter</button>
      </form>

      <div className="mb-2">
        <label className="form-label me-2">Trier par :</label>
        <select className="form-select w-auto d-inline" onChange={e => setSortBy(e.target.value)} value={sortBy}>
          <option value="date">Date</option>
          <option value="name">Nom</option>
        </select>
      </div>

      {sortedDocuments.length === 0 ? <p>Aucun document</p> : (
        <div className="row">
          {sortedDocuments.map(doc => (
            <div key={doc.id} className="col-md-4 mb-3">
              <div className="card">
                <div className="card-body">
                  <h6>{doc.nom || "Sans nom"}</h6>
                  <small className="text-muted">{doc.type}</small>
                  <div className="mt-2">
                    {isImage(doc.fichier) ? (
                      <img src={`/storage/${doc.fichier}`} alt="preview" className="img-fluid rounded" />
                    ) : isPDF(doc.fichier) ? (
                      <iframe src={`/storage/${doc.fichier}`} width="100%" height="200px" title="PDF Preview"></iframe>
                    ) : (
                      <p className="text-muted">Fichier non prévisualisable</p>
                    )}
                  </div>
                  <div className="mt-2 d-flex justify-content-between">
                    <a
                      href={`/storage/${doc.fichier}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn btn-sm btn-outline-primary"
                    >
                      Télécharger
                    </a>
                    <button
                      className="btn btn-sm btn-outline-danger"
                      onClick={() => handleDelete(doc.id)}
                    >
                      Supprimer
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default PersonnelDocuments;
