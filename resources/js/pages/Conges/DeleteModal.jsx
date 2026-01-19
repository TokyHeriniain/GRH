// DeleteModal.jsx
import React from "react";
import { Modal, Button } from "react-bootstrap";

export default function DeleteModal({ show, onClose, onConfirm }) {
  return (
    <Modal show={show} centered onHide={onClose}>
      <Modal.Header closeButton>
        <Modal.Title>Confirmation de suppression</Modal.Title>
      </Modal.Header>

      <Modal.Body>
        Voulez-vous vraiment supprimer ce congé ? Cette action est irréversible.
      </Modal.Body>

      <Modal.Footer>
        <Button variant="secondary" onClick={onClose}>
          Annuler
        </Button>
        <Button variant="danger" onClick={onConfirm}>
          Supprimer
        </Button>
      </Modal.Footer>
    </Modal>
  );
}
