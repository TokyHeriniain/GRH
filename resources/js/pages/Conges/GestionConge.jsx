import React, { useEffect, useState } from "react";
import { Pagination, Tab, Tabs } from "react-bootstrap";
import api from "axios";
import NavigationLayout from "../../components/NavigationLayout";
import { ToastContainer, toast } from "react-toastify";

import CongeForm from "./CongeForm";
import CongeFilters from "./CongeFilters";
import CongeTable from "./CongeTable";
import RejectModal from "./RejectModal";
import DeleteModal from "./DeleteModal";
import HistoriqueConge from "./HistoriqueConge";
import ReliquatsEnCours from "./ReliquatsEnCours";
import CongeDetailModal from "./CongeDetailModal";

export default function GestionConge() {
  const [leaves, setLeaves] = useState([]);
  const [personnels, setPersonnels] = useState([]);
  const [leaveTypes, setLeaveTypes] = useState([]);
  const [filter, setFilter] = useState("all");
  const [search, setSearch] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [reloadLeaves, setReloadLeaves] = useState(0);
  const [activeTab, setActiveTab] = useState("gestion");

  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectReason, setRejectReason] = useState("");
  const [rejectLeaveId, setRejectLeaveId] = useState(null);

  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [toDeleteId, setToDeleteId] = useState(null);
  const [removing, setRemoving] = useState([]);

  const [showDetail, setShowDetail] = useState(false);
  const [selectedLeave, setSelectedLeave] = useState(null);


  const [editingId, setEditingId] = useState(null);
  const [form, setForm] = useState({
    personnel_id: "",
    personnel_label: "",
    leave_type_id: "",
    leave_type_label: "",
    date_debut: "",
    date_fin: "",
    heure_debut: "08:00",
    heure_fin: "17:30",
    raison: "",
  });

  const [selectedPersonnelId, setSelectedPersonnelId] = useState(null);

  useEffect(() => {
    fetchData(currentPage);
  }, [currentPage, reloadLeaves]);

  useEffect(() => {
    fetchPersonnels();
    fetchLeaveTypes();
  }, []);


  const fetchData = async (page = 1) => {
    try {
      const res = await api.get(`/api/rh/leaves?status=all&page=${page}&per_page=5`);
      setLeaves(res.data.data);          // Assurez-vous que votre controller renvoie bien { data, current_page, last_page }
      setTotalPages(res.data.last_page);
      setCurrentPage(res.data.current_page);
    } catch (err) {
      console.error("Erreur fetchData:", err.response || err);
    }
  };


  const fetchPersonnels = async () => {
    const res = await api.get("/api/personnels");
    setPersonnels(res.data.data);
  };

  const fetchLeaveTypes = async () => {
    const res = await api.get("/api/leave-types");
    setLeaveTypes(res.data);
  };

  const rhValidate = async (id) => {
    try {
      const res = await api.post(`/api/rh/leaves/${id}/approve`);
      setLeaves((prev) => prev.map((l) => (l.id === id ? res.data.data : l)));
      setReloadLeaves((r) => r + 1);
      toast.success("Congé validé RH");
    } catch (error) {
      toast.error(error.response?.data?.message || "Erreur validation RH");
    }
  };

  const rejectLeave = async (leave, motif) => {
    try {
      const res = await api.post(`/api/rh/leaves/${leave.id}/reject`, {
        rejection_reason: motif,
      });
      setLeaves((prev) => prev.map((l) => (l.id === leave.id ? res.data.data : l)));
      setReloadLeaves((r) => r + 1);
      toast.success("Congé rejeté RH");
    } catch (error) {
      toast.error(error.response?.data?.message || "Erreur rejet RH");
    }
  };

  const confirmDelete = (id) => {
    setToDeleteId(id);
    setShowDeleteModal(true);
  };

  const handleDelete = async () => {
    const id = toDeleteId;
    setShowDeleteModal(false);
    setRemoving((prev) => [...prev, id]);

    try {
      await api.delete(`/api/rh/leaves/${id}`);
      await fetchData(currentPage);
      setReloadLeaves((r) => r + 1);
      toast.success("Congé supprimé");
    } catch {
      toast.error("Erreur suppression");
    } finally {
      setRemoving((prev) => prev.filter((x) => x !== id));
    }
  };

  const handleEdit = (leave) => {
    setEditingId(leave.id);
    setForm({
      personnel_id: leave.personnel_id,
      personnel_label: `${leave.personnel?.matricule} - ${leave.personnel?.nom} ${leave.personnel?.prenom}`,
      leave_type_id: leave.leave_type_id,
      leave_type_label: leave.leave_type?.nom,
      date_debut: leave.date_debut,
      date_fin: leave.date_fin,
      heure_debut: leave.heure_debut?.slice(0, 5),
      heure_fin: leave.heure_fin?.slice(0, 5),
      raison: leave.raison || "",
    });
  };
  const handleViewDetail = (leave) => {
    setSelectedLeave(leave);
    setShowDetail(true);
  };


  return (
    <NavigationLayout>
      <div className="container mt-4">
        <Tabs activeKey={activeTab} onSelect={setActiveTab} className="mb-3">
          <Tab eventKey="gestion" title="Gestion des congés">
            <CongeForm
              personnels={personnels}
              leaveTypes={leaveTypes}
              setReloadLeaves={setReloadLeaves}
              editingId={editingId}
              form={form}
              setForm={setForm}
              clearEditing={() => setEditingId(null)}
              notifySuccess={toast.success}
              setSelectedPersonnelId={setSelectedPersonnelId}
            />
            <CongeFilters
              search={search}
              setSearch={setSearch}
              filter={filter}
              setFilter={setFilter}
            />
            <CongeTable
              leaves={leaves}
              search={search}
              filter={filter}
              onEdit={handleEdit}
              onDeleteRequest={confirmDelete}
              removing={removing}
              rhValidate={rhValidate}
              onReject={setShowRejectModal}
              onRejectSelect={setRejectLeaveId}
              onViewDetail={handleViewDetail}
            />
            <Pagination className="justify-content-center mt-3">
              <Pagination.Prev
                disabled={currentPage === 1}
                onClick={() => fetchData(currentPage - 1)}
              />
              <Pagination.Item active>{currentPage}</Pagination.Item>
              <Pagination.Next
                disabled={currentPage === totalPages}
                onClick={() => fetchData(currentPage + 1)}
              />
            </Pagination>

            <RejectModal
              show={showRejectModal}
              onHide={() => setShowRejectModal(false)}
              reason={rejectReason}
              setReason={setRejectReason}
              confirmReject={() => {
                const leave = leaves.find((l) => l.id === rejectLeaveId);
                if (leave) rejectLeave(leave, rejectReason);
                setShowRejectModal(false);
                setRejectReason("");
              }}
            />
            <DeleteModal
              show={showDeleteModal}
              onClose={() => setShowDeleteModal(false)}
              onConfirm={handleDelete}
            />
            <CongeDetailModal
              show={showDetail}
              onHide={() => setShowDetail(false)}
              leave={selectedLeave}
            />
          </Tab>

          <Tab eventKey="historique" title="Historique global">
            <HistoriqueConge reload={reloadLeaves} />
          </Tab>

          <Tab eventKey="reliquat" title="Reliquats congés">
            <ReliquatsEnCours reload={reloadLeaves} />
          </Tab>
        </Tabs>
      </div>

      <ToastContainer position="bottom-right" autoClose={3000} />
    </NavigationLayout>
  );
}
