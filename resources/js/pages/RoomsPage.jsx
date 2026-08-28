import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { BedDouble, Check, ChevronDown, ExternalLink, MapPin, Users, Wifi } from 'lucide-react';
import axios from 'axios';

const api = axios.create({ baseURL: '/api', headers: { Accept: 'application/json' } });
const categories = ['All rooms', 'Silver Rooms', 'Premium Rooms', 'Gold Rooms', 'Beach Apartment'];
const categoryFor = (room) => room.room_category === 'Basic' ? 'Silver Rooms' : room.room_category;
const amenities = {
  Basic: ['Free swimming', 'Comfort stay', 'Beach access', 'Wi-Fi'],
  Premium: ['Free swimming', 'Comfort stay', 'Beach access', 'Wi-Fi'],
  Gold: ['Breakfast for 1', 'Free laundry', 'Free swimming', 'Larger room'],
  'Beach Apartment': ['Beach view', 'Breakfast for 2', 'Free swimming', 'Beach access'],
};
const formatMoney = (value, currency) => new Intl.NumberFormat('en-NG', { style: 'currency', currency, maximumFractionDigits: 0 }).format(value);

export default function RoomsPage() {
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [category, setCategory] = useState('All rooms');
  const [compareMode, setCompareMode] = useState(false);
  const [selected, setSelected] = useState([]);
  const [currency, setCurrency] = useState('NGN');

  useEffect(() => {
    api.get('/rooms').then(({ data }) => setRooms(data)).finally(() => setLoading(false));
  }, []);

  const visible = useMemo(() => rooms.filter((room) => category === 'All rooms' || categoryFor(room) === category), [rooms, category]);
  const soldOut = visible.filter((room) => room.is_booked && room.is_booked !== 'no').length;
  const toggleComparison = (id) => setSelected((current) => current.includes(id) ? current.filter((roomId) => roomId !== id) : [...current, id]);

  return <main className="room-selection-page">
    <aside className="stay-summary" aria-label="Your stay summary">
      <div className="stay-summary-heading"><strong>Your stay</strong><Link to="/book">Edit stay</Link></div>
      <img src="images/hero.jpg" alt="Iceland Beach Resort" />
      <h2>Iceland Beach Resort</h2>
      <p><MapPin size={16} /> Lagos, Nigeria<br />New Iceland Beach Resort</p>
      <p><BedDouble size={16} /> Choose dates and a room to continue</p>
      <a href="https://www.google.com/maps/place/New+Iceland+Beach+Resort/@6.4263852,3.6111122,18z/data=!4m6!3m5!1s0x103bf799497e6475:0x1042d67e8aa0e790!8m2!3d6.4265846!4d3.6092732!16s%2Fg%2F11x1sdvpjb?entry=ttu&g_ep=EgoyMDI2MDgxOS4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noreferrer">Resort Location <ExternalLink size={14} /></a>
    </aside>

    <section className="room-selection-content">
      <div className="room-step-row">
        <div><p>Step 1 of 4</p><h1>Select a Room</h1><div className="step-progress" aria-label="Step 1 of 4"><span /></div></div>
        <label className="currency-select">Currency<select value={currency} onChange={(event) => setCurrency(event.target.value)} aria-label="Select currency"><option value="NGN">NGN ₦</option><option value="USD">USD $</option></select><ChevronDown size={16} /></label>
      </div>

      <div className="room-inclusions"><strong>Every stay includes</strong><div><span><Check /> Beach access</span><span><Check /> Free Wi-Fi</span><span><Check /> Swimming pool access</span><span><Check /> On-site support</span></div></div>

      <div className="room-controls">
        <div className="room-filter-group" role="group" aria-label="Filter rooms"><span>Filter by:</span>{categories.map((item) => <label key={item}><input type="radio" name="room-category" checked={category === item} onChange={() => setCategory(item)} /> {item}</label>)}</div>
        <label className="compare-toggle"><input type="checkbox" checked={compareMode} onChange={(event) => { setCompareMode(event.target.checked); if (!event.target.checked) setSelected([]); }} /><span aria-hidden="true" /> Compare rooms</label>
      </div>

      <div className={`room-results-banner ${visible.length === 0 ? 'empty' : ''}`} aria-live="polite">{loading ? 'Loading rooms…' : visible.length === 0 ? <>No rooms match these filters. <button onClick={() => setCategory('All rooms')}>Clear filters</button></> : <>{visible.length} room{visible.length === 1 ? '' : 's'} found. {soldOut > 0 ? `${soldOut} sold out. ` : ''}Showing average price per night.</>}</div>

      {compareMode && <div className="compare-status">{selected.length} room{selected.length === 1 ? '' : 's'} selected for comparison.</div>}
      <div className="room-selection-grid">
        {loading ? Array.from({ length: 4 }).map((_, index) => <article className="room-selection-card loading" key={index}><div /><div /><div /></article>) : visible.map((room) => {
          const roomAmenities = amenities[room.room_category] || ['Beach access', 'Comfort stay', 'On-site support', 'Wi-Fi'];
          const isSoldOut = room.is_booked && room.is_booked !== 'no';
          const isSelected = selected.includes(room.id);
          return <article className={`room-selection-card ${isSoldOut ? 'sold-out' : ''} ${isSelected ? 'selected' : ''}`} key={room.id}>
            <div className="room-photo-wrap"><img src={room.room_image || '/legacy-images/Double Bed (2).jpeg'} onError={(event) => { event.currentTarget.src = '/legacy-images/Double Bed (2).jpeg'; }} alt={room.room_name} />{isSoldOut && <span>Sold out</span>}</div>
            <div className="room-card-body"><p className="room-category-label">{categoryFor(room)}</p><h2>{room.room_name}</h2><a href={`#room-${room.id}`}>View room details</a><ul>{roomAmenities.map((amenity, index) => <li key={amenity}>{index === 0 ? <Users size={15} /> : index === 1 ? <BedDouble size={15} /> : <Wifi size={15} />}{amenity}</li>)}</ul><strong>{formatMoney(room.room_price, currency)} <small>per night</small></strong><div className="room-card-actions">{compareMode && <label><input type="checkbox" checked={isSelected} onChange={() => toggleComparison(room.id)} /> Compare</label>}<Link to={`/book?room=${room.id}`} className="select-room-button" aria-disabled={isSoldOut}>{isSoldOut ? 'Unavailable' : 'Select room'}</Link></div></div>
          </article>;
        })}
      </div>
    </section>
  </main>;
}