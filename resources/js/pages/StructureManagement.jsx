import React, { useEffect, useState } from 'react';
import api from '../axios';
import { Button, Modal, Form } from 'react-bootstrap';
import NavigationLayout from "../components/NavigationLayout";

const API_MAP = {
  directions: '/api/directions',
  services: '/api/services',
  fonctions: '/api/fonctions',
};

export default function StructureManagement() {
  const [activeTab, setActiveTab] = useState('directions');
  const [items, setItems] = useState([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [name, setName] = useState('');
  const [directionId, setDirectionId] = useState('');
  const [serviceId, setServiceId] = useState('');
  const [directions, setDirections] = useState([]);
  const [services, setServices] = useState([]);

  useEffect(() => {
    fetchItems();
  }, [activeTab]);

  const fetchItems = async () => {
    try {
      const res = await api.get(API_MAP[activeTab]);
      setItems(res.data);

      if (activeTab === 'services' || activeTab === 'fonctions') {
        const dirRes = await api.get(API_MAP['directions']);
        setDirections(dirRes.data);
      }
      if (activeTab === 'fonctions') {
        const servRes = await api.get(API_MAP['services']);
        setServices(servRes.data);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleSave = async () => {
    try {
      const payload = { nom: name };

      if (activeTab === 'services') {
        payload.direction_id = directionId;
      } else if (activeTab === 'fonctions') {
        payload.service_id = serviceId;
      }

      if (editingItem) {
        await api.put(`${API_MAP[activeTab]}/${editingItem.id}`, payload);
      } else {
        await api.post(API_MAP[activeTab], payload);
      }

      fetchItems();
      closeModal();
    } catch (err) {
      console.error(err.response?.data?.errors || err);
    }
  };

  const handleEdit = (item) => {
    setEditingItem(item);
    setName(item.nom);
    if (activeTab === 'services') {
      setDirectionId(item.direction_id || '');
    } else if (activeTab === 'fonctions') {
      setServiceId(item.service_id || '');
    }
    setModalOpen(true);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Confirmer la suppression ?')) return;
    try {
      await api.delete(`${API_MAP[activeTab]}/${id}`);
      fetchItems();
    } catch (err) {
      console.error(err);
    }
  };

  const openModal = () => {
    setEditingItem(null);
    setName('');
    setDirectionId('');
    setServiceId('');
    setModalOpen(true);
  };

  const closeModal = () => {
    setModalOpen(false);
    setName('');
    setEditingItem(null);
    setDirectionId('');
    setServiceId('');
  };

  return (
    <NavigationLayout>
      <div className="container mt-4">
      <h4>Gestion des structures</h4>
      <ul className="nav nav-tabs mb-3">
        {['directions', 'services', 'fonctions'].map((tab) => (
          <li className="nav-item" key={tab}>
            <button
              className={`nav-link ${activeTab === tab ? 'active' : ''}`}
              onClick={() => setActiveTab(tab)}
            >
              {tab.charAt(0).toUpperCase() + tab.slice(1)}
            </button>
          </li>
        ))}
      </ul>

      <Button variant="primary" className="mb-3" onClick={openModal}>➕ Ajouter</Button>

      <table className="table table-bordered">
        <thead>
        <tr>
            <th>#</th>
            <th>Nom</th>
            {activeTab === 'services' && <th>Direction</th>}
            {activeTab === 'fonctions' && (
            <>
                <th>Service</th>
                <th>Direction</th>
            </>
            )}
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        {Array.isArray(items) && items.map((item, index) => (
            <tr key={item.id}>
            <td>{index + 1}</td>
            <td>{item.nom}</td>

            {activeTab === 'services' && <td>{item.direction?.nom || '-'}</td>}

            {activeTab === 'fonctions' && (
                <>
                <td>{item.service?.nom || '-'}</td>
                <td>{item.service?.direction?.nom || '-'}</td>
                </>
            )}

            <td>
                <Button size="sm" variant="warning" onClick={() => handleEdit(item)} className="me-2">✏️</Button>
                <Button size="sm" variant="danger" onClick={() => handleDelete(item.id)}>🗑️</Button>
            </td>
            </tr>
        ))}
        </tbody>
      </table>

      <Modal show={modalOpen} onHide={closeModal} centered>
        <Modal.Header closeButton>
          <Modal.Title>{editingItem ? 'Modifier' : 'Ajouter'} {activeTab.slice(0, -1)}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form.Group className="mb-3">
            <Form.Label>Nom</Form.Label>
            <Form.Control
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder={`Nom de la ${activeTab.slice(0, -1)}`}
            />
          </Form.Group>

          {activeTab === 'services' && (
            <Form.Group className="mb-3">
              <Form.Label>Direction</Form.Label>
              <Form.Select
                value={directionId}
                onChange={(e) => setDirectionId(e.target.value)}
              >
                <option value="">-- Sélectionner une direction --</option>
                {directions.map(dir => (
                  <option key={dir.id} value={dir.id}>{dir.nom}</option>
                ))}
              </Form.Select>
            </Form.Group>
          )}

          {activeTab === 'fonctions' && (
            <Form.Group className="mb-3">
              <Form.Label>Service</Form.Label>
              <Form.Select
                value={serviceId}
                onChange={(e) => setServiceId(e.target.value)}
              >
                <option value="">-- Sélectionner un service --</option>
                {services.map(serv => (
                  <option key={serv.id} value={serv.id}>{serv.nom}</option>
                ))}
              </Form.Select>
            </Form.Group>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={closeModal}>Annuler</Button>
          <Button variant="primary" onClick={handleSave}>Enregistrer</Button>
        </Modal.Footer>
      </Modal>
    </div>
    </NavigationLayout>
    
  );
}
