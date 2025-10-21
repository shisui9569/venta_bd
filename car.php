<?php
session_start();
require_once "conexion.php";

// Verificar si hay acciones en el carrito (eliminar, actualizar cantidad)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eliminar'])) {
        $id_eliminar = $_POST['id_producto'];
        if (isset($_SESSION['carrito'][$id_eliminar])) {
            unset($_SESSION['carrito'][$id_eliminar]);
            $_SESSION['mensaje'] = "Producto eliminado del carrito";
        }
    }
    
    if (isset($_POST['actualizar_cantidad'])) {
        $id_actualizar = $_POST['id_producto'];
        $nueva_cantidad = max(1, intval($_POST['cantidad'])); // Mínimo 1
        
        if (isset($_SESSION['carrito'][$id_actualizar])) {
            $_SESSION['carrito'][$id_actualizar]['cantidad'] = $nueva_cantidad;
            $_SESSION['mensaje'] = "Cantidad actualizada";
        }
    }
}

$carrito = $_SESSION['carrito'] ?? [];
$subtotal = 0;
$count = 0;

foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
    $count += $item['cantidad'];
}



// Guardar total en sesión para procesar_pedido.php
$_SESSION['total_pedido'] = $total;

// Datos de departamentos, provincias y distritos del Perú
$peru_ubicaciones = array(
    "Amazonas" => array(
        "Chachapoyas" => array("Chachapoyas", "Asunción", "Balsas", "Cheto", "Chiliquin", "Chuquibamba", "Granada", "Huancas", "La Jalca", "Leimebamba", "Levanto", "Magdalena", "Mariscal Castilla", "Molinopampa", "Montevideo", "Olleros", "Quinjalca", "San Francisco de Daguas", "San Isidro de Maino", "Soloco", "Sonche"),
        "Bagua" => array("Bagua", "Aramango", "Copallin", "El Parco", "Imaza", "La Peca"),
        "Bongará" => array("Jumbilla", "Chisquilla", "Churuja", "Corosha", "Cuispes", "Florida", "Jazán", "Recta", "San Carlos", "Shipasbamba", "Valera", "Yambrasbamba"),
        "Condorcanqui" => array("Santa María de Nieva", "El Cenepa", "Río Santiago"),
        "Luya" => array("Lámud", "Camporredondo", "Cocabamba", "Colcamar", "Conila", "Inguilpata", "Longuita", "Lonya Chico", "Luya", "Luya Viejo", "María", "Ocalli", "Ocumal", "Pisuquía", "Providencia", "San Cristóbal", "San Francisco del Yeso", "San Jerónimo", "San Juan de Lopecancha", "Santa Catalina", "Santo Tomás", "Tingo", "Trita"),
        "Rodríguez de Mendoza" => array("San Nicolás", "Chirimoto", "Cochamal", "Huambo", "Limabamba", "Longar", "Mariscal Benavides", "Milpuc", "Omia", "Santa Rosa", "Totora", "Vista Alegre"),
        "Utcubamba" => array("Bagua Grande", "Cajaruro", "Cumba", "El Milagro", "Jamalca", "Lonya Grande", "Yamón")
    ),
    "Áncash" => array(
        "Huaraz" => array("Huaraz", "Cochabamba", "Colcabamba", "Huanchay", "Independencia", "Jangas", "La Libertad", "Olleros", "Pampas Grande", "Pariacoto", "Pira", "Tarica"),
        "Aija" => array("Aija", "Coris", "Huacllán", "La Merced", "Succha"),
        "Antonio Raymondi" => array("Llamellín", "Aczo", "Chaccho", "Chingas", "Mirgas", "San Juan de Rontoy"),
        "Asunción" => array("Chacas", "Acochaca"),
        "Bolognesi" => array("Chiquián", "Abelardo Pardo Lezameta", "Antonio Raymondi", "Aquia", "Cajacay", "Canis", "Colquioc", "Huallanca", "Huasta", "Huayllacayan", "La Primavera", "Mangas", "Pacllón", "San Miguel de Corpanqui", "Ticllos"),
        "Carhuaz" => array("Carhuaz", "Acopampa", "Amashca", "Anta", "Ataquero", "Marcará", "Pariahuanca", "San Miguel de Aco", "Shilla", "Tinco", "Yungar"),
        "Carlos Fermín Fitzcarrald" => array("San Luis", "San Nicolás", "Yauya"),
        "Casma" => array("Casma", "Buena Vista Alta", "Comandante Noel", "Yautan"),
        "Corongo" => array("Corongo", "Aco", "Bambas", "Cusca", "La Pampa", "Yanac", "Yupan"),
        "Huari" => array("Huari", "Anra", "Cajay", "Chavín de Huantar", "Huacachi", "Huacchis", "Huachis", "Huantar", "Masin", "Paucas", "Ponto", "Rahuapampa", "Rapayan", "San Marcos", "San Pedro de Chana", "Uco"),
        "Huarmey" => array("Huarmey", "Cochapeti", "Culebras", "Huayan", "Malvas"),
        "Huaylas" => array("Caraz", "Huallanca", "Huata", "Huaylas", "Mato", "Pamparomas", "Pueblo Libre", "Santa Cruz", "Santo Toribio", "Yuracmarca"),
        "Mariscal Luzuriaga" => array("Piscobamba", "Casca", "Eleazar Guzmán Barrón", "Fidel Olivas Escudero", "Llama", "Llumpa", "Lucma", "Musga"),
        "Ocros" => array("Ocros", "Acas", "Cajamarquilla", "Carhuapampa", "Cochas", "Congas", "Llipa", "San Cristóbal de Rajan", "San Pedro", "Santiago de Chilcas"),
        "Pallasca" => array("Cabana", "Bolognesi", "Conchucos", "Huacaschuque", "Huandoval", "Lacabamba", "Llapo", "Pallasca", "Pampas", "Santa Rosa", "Tauca"),
        "Pomabamba" => array("Pomabamba", "Huayllan", "Parobamba", "Quinuabamba"),
        "Recuay" => array("Recuay", "Catac", "Cotaparaco", "Huayllapampa", "Llacllin", "Marca", "Pampas Chico", "Pararin", "Tapacocha", "Ticapampa"),
        "Santa" => array("Chimbote", "Cáceres del Perú", "Coishco", "Macate", "Moro", "Nepeña", "Samanco", "Santa"),
        "Sihuas" => array("Sihuas", "Acobamba", "Alfonso Ugarte", "Cashapampa", "Chingalpo", "Huayllabamba", "Quiches", "Ragash", "San Juan", "Sicsibamba"),
        "Yungay" => array("Yungay", "Cascapara", "Mancos", "Matacoto", "Quillo", "Ranrahirca", "Shupluy", "Yanama")
    ),
    "Apurímac" => array(
        "Abancay" => array("Abancay", "Chacoche", "Circa", "Curahuasi", "Huanipaca", "Lambrama", "Pichirhua", "San Pedro de Cachora", "Tamburco"),
        "Andahuaylas" => array("Andahuaylas", "Andarapa", "Chiara", "Huancarama", "Huancaray", "Huayana", "Kishuara", "Pacobamba", "Pacucha", "Pampachiri", "Pomacocha", "San Antonio de Cachi", "San Jerónimo", "San Miguel de Chaccrapampa", "Santa María de Chicmo", "Talavera", "Tumay Huaraca", "Turpo"),
        "Antabamba" => array("Antabamba", "El Oro", "Huaquirca", "Juan Espinoza Medrano", "Oropesa", "Pachaconas", "Sabaino"),
        "Aymaraes" => array("Chalhuanca", "Capaya", "Caraybamba", "Chapimarca", "Colcabamba", "Cotaruse", "Huayllo", "Justo Apu Sahuaraura", "Lucre", "Pocohuanca", "San Juan de Chacña", "Sañayca", "Soraya", "Tapairihua", "Tintay", "Toraya", "Yanaca"),
        "Cotabambas" => array("Tambobamba", "Cotabambas", "Coyllurqui", "Haquira", "Mara", "Challhuahuacho"),
        "Chincheros" => array("Chincheros", "Anco_Huallo", "Cocharcas", "Huaccana", "Ocobamba", "Ongoy", "Uranmarca", "Ranracancha"),
        "Grau" => array("Chuquibambilla", "Curpahuasi", "Gamarra", "Huayllati", "Mamara", "Micaela Bastidas", "Pataypampa", "Progreso", "San Antonio", "Santa Rosa", "Turpay", "Vilcabamba", "Virundo", "Curasco")
    ),
    "Arequipa" => array(
        "Arequipa" => array("Arequipa", "Alto Selva Alegre", "Cayma", "Cerro Colorado", "Characato", "Chiguata", "Jacobo Hunter", "La Joya", "Mariano Melgar", "Miraflores", "Mollebaya", "Paucarpata", "Pocsi", "Polobaya", "Quequeña", "Sabandia", "Sachaca", "San Juan de Siguas", "San Juan de Tarucani", "Santa Isabel de Siguas", "Santa Rita de Siguas", "Socabaya", "Tiabaya", "Uchumayo", "Vitor", "Yanahuara", "Yarabamba", "Yura"),
        "Camaná" => array("Camaná", "José María Quimper", "Mariano Nicolás Valcárcel", "Mariscal Cáceres", "Nicolás de Pierola", "Ocoña", "Quilca", "Samuel Pastor"),
        "Caravelí" => array("Caravelí", "Acarí", "Atico", "Atiquipa", "Bella Unión", "Cahuacho", "Chala", "Chaparra", "Huanuhuanu", "Jaqui", "Lomas", "Quicacha", "Yauca"),
        "Castilla" => array("Aplao", "Andagua", "Ayo", "Chachas", "Chilcaymarca", "Choco", "Huancarqui", "Machaguay", "Orcopampa", "Pampacolca", "Tipan", "Uñon", "Uraca", "Viraco"),
        "Caylloma" => array("Chivay", "Achoma", "Cabanaconde", "Callalli", "Caylloma", "Coporaque", "Huambo", "Huanca", "Ichupampa", "Lari", "Lluta", "Maca", "Madrigal", "Majes", "San Antonio de Chuca", "Sibayo", "Tapay", "Tisco", "Tuti", "Yanque"),
        "Condesuyos" => array("Chuquibamba", "Andaray", "Cayarani", "Chichas", "Iray", "Río Grande", "Salamanca", "Yanaquihua"),
        "Islay" => array("Mollendo", "Cocachacra", "Dean Valdivia", "Islay", "Mejía", "Punta de Bombón"),
        "La Uniòn" => array("Cotahuasi", "Alca", "Charcana", "Huaynacotas", "Pampamarca", "Puyca", "Quechualla", "Sayla", "Tauria", "Tomepampa", "Toro")
    ),
    "Ayacucho" => array(
        "Huamanga" => array("Ayacucho", "Acocro", "Acos Vinchos", "Carmen Alto", "Chiara", "Ocros", "Pacaycasa", "Quinua", "San José de Ticllas", "San Juan Bautista", "Santiago de Pischa", "Socos", "Tambillo", "Vinchos", "Jesús Nazareno", "Andrés Avelino Cáceres Dorregaray"),
        "Cangallo" => array("Cangallo", "Chuschi", "Los Morochucos", "María Parado de Bellido", "Paras", "Totos"),
        "Huanca Sancos" => array("Sancos", "Carapo", "Sacsamarca", "Santiago de Lucanamarca"),
        "Huanta" => array("Huanta", "Ayahuanco", "Huamanguilla", "Iguain", "Luricocha", "Santillana", "Sivia", "Llochegua"),
        "La Mar" => array("San Miguel", "Anco", "Ayna", "Chilcas", "Chungui", "Luis Carranza", "Santa Rosa", "Tambo"),
        "Lucanas" => array("Puquio", "Aucara", "Cabana", "Carmen Salcedo", "Chaviña", "Chipao", "Huac-Huas", "Laramate", "Leoncio Prado", "Llauta", "Lucanas", "Ocaña", "Otoca", "Saisa", "San Cristóbal", "San Juan", "San Pedro", "San Pedro de Palco", "Sancos", "Santa Ana de Huaycahuacho", "Santa Lucia"),
        "Parinacochas" => array("Coracora", "Chumpi", "Coronel Castañeda", "Pacapausa", "Pullo", "Puyusca", "San Francisco de Ravacayco", "Upahuacho"),
        "Páucar del Sara Sara" => array("Pausa", "Colta", "Corculla", "Lampa", "Marcabamba", "Oyolo", "Pararca", "San Javier de Alpabamba", "San José de Ushua", "Sara Sara"),
        "Sucre" => array("Querobamba", "Belen", "Chalcos", "Chilcayoc", "Huacaña", "Morcolla", "Paico", "San Pedro de Larcay", "San Salvador de Quije", "Santiago de Paucaray", "Soras"),
        "Víctor Fajardo" => array("Huancapi", "Alcamenca", "Apongo", "Asquipata", "Canaria", "Cayara", "Colca", "Huamanquiquia", "Huancaraylla", "Hualla", "Sarhua", "Vilcanchos"),
        "Vilcas Huamán" => array("Vilcas Huaman", "Accomarca", "Carhuanca", "Concepción", "Huambalpa", "Independencia", "Saurama", "Vischongo")
    ),
    "Cajamarca" => array(
        "Cajamarca" => array("Cajamarca", "Asunción", "Chetilla", "Cospan", "Encañada", "Jesús", "Llacanora", "Los Baños del Inca", "Magdalena", "Matara", "Namora", "San Juan", "San Pablo"),
        "Cajabamba" => array("Cajabamba", "Cachachi", "Condebamba", "Sitacocha"),
        "Celendín" => array("Celendín", "Chumuch", "Cortegana", "Huasmin", "Jorge Chávez", "José Gálvez", "Miguel Iglesias", "Oxamarca", "Sorochuco", "Sucre", "Utco"),
        "Chota" => array("Chota", "Anguia", "Chadin", "Chiguirip", "Chimban", "Choropampa", "Cochabamba", "Conchan", "Huambos", "Lajas", "Llama", "Miracosta", "Paccha", "Pion", "Querocoto", "San Juan de Licupis", "Tacabamba", "Tocmoche", "Chalamarca"),
        "Contumazá" => array("Contumazá", "Chilete", "Cupisnique", "Guzmango", "San Benito", "Santa Cruz de Toledo", "Tantarica", "Yonan"),
        "Cutervo" => array("Cutervo", "Callayuc", "Choros", "Cujillo", "La Ramada", "Pimpingos", "Querocotillo", "San Andrés de Cutervo", "San Juan de Cutervo", "San Luis de Lucma", "Santa Cruz", "Santo Domingo de la Capilla", "Santo Tomas", "Socota", "Toribio Casanova"),
        "Hualgayoc" => array("Bambamarca", "Chugur", "Hualgayoc"),
        "Jaén" => array("Jaén", "Bellavista", "Chontali", "Colasay", "Huabal", "Las Pirias", "Pomahuaca", "Pucara", "Sallique", "San Felipe", "San José del Alto", "Santa Rosa"),
        "San Ignacio" => array("San Ignacio", "Chirinos", "Huarango", "La Coipa", "Namballe", "San José de Lourdes", "Tabaconas"),
        "San Marcos" => array("San Marcos", "Chancay", "Eduardo Villanueva", "Gregorio Pita", "Ichocan", "José Manuel Quiroz", "José Sabogal", "Pedro Gálvez"),
        "San Miguel" => array("San Miguel", "Bolívar", "Calquis", "Catilluc", "El Prado", "La Florida", "Llapa", "Nanchoc", "Niepos", "San Gregorio", "San Silvestre de Cochan", "Tongod", "Unión Agua Blanca"),
        "San Pablo" => array("San Pablo", "San Bernardino", "San Luis", "Tumbaden"),
        "Santa Cruz" => array("Santa Cruz", "Andabamba", "Catache", "Chancaybaños", "La Esperanza", "Ninabamba", "Pulan", "Saucepampa", "Sexi", "Uticyacu", "Yauyucan")
    ),
    "Callao" => array(
        "Callao" => array("Callao", "Bellavista", "Carmen de la Legua Reynoso", "La Perla", "La Punta", "Ventanilla")
    ),
    "Cusco" => array(
        "Cusco" => array("Cusco", "Ccorca", "Poroy", "San Jerónimo", "San Sebastian", "Santiago", "Saylla", "Wanchaq"),
        "Acomayo" => array("Acomayo", "Acopia", "Acos", "Mosoc Llacta", "Pomacanchi", "Rondocan", "Sangarara"),
        "Anta" => array("Anta", "Ancahuasi", "Cachimayo", "Chinchaypujio", "Huarocondo", "Limatambo", "Mollepata", "Pucyura", "Zurite"),
        "Calca" => array("Calca", "Coya", "Lamay", "Lares", "Pisac", "San Salvador", "Taray", "Yanatile"),
        "Canas" => array("Yanaoca", "Checca", "Kunturkanki", "Langui", "Layo", "Pampamarca", "Quehue", "Tupac Amaru"),
        "Canchis" => array("Sicuani", "Checacupe", "Combapata", "Marangani", "Pitumarca", "San Pablo", "San Pedro", "Tinta"),
        "Chumbivilcas" => array("Santo Tomas", "Capacmarca", "Chamaca", "Colquemarca", "Livitaca", "Llusco", "Quiñota", "Velille"),
        "Espinar" => array("Espinar", "Condoroma", "Coporaque", "Ocoruro", "Pallpata", "Pichigua", "Suyckutambo", "Alto Pichigua"),
        "La Convención" => array("Quillabamba", "Echarate", "Huayopata", "Maranura", "Ocobamba", "Quebrada Honda", "Kimbiri", "Santa Teresa", "Vilcabamba", "Pichari", "Inkawasi"),
        "Paruro" => array("Paruro", "Accha", "Ccapi", "Colcha", "Huanoquite", "Omacha", "Paccaritambo", "Pillpinto", "Yaurisque"),
        "Paucartambo" => array("Paucartambo", "Caicay", "Challabamba", "Colquepata", "Huancarani", "Kosñipata"),
        "Quispicanchi" => array("Urcos", "Andahuaylillas", "Camanti", "Ccarhuayo", "Ccatca", "Cusipata", "Huaro", "Lucre", "Marcapata", "Ocongate", "Oropesa", "Quiquijana"),
        "Urubamba" => array("Urubamba", "Chinchero", "Huayllabamba", "Machupicchu", "Maras", "Ollantaytambo", "Yucay")
    ),
    "Huancavelica" => array(
        "Huancavelica" => array("Huancavelica", "Acobambilla", "Acoria", "Conayca", "Cuenca", "Huachocolpa", "Huayllahuara", "Izcuchaca", "Laria", "Manta", "Mariscal Cáceres", "Moya", "Nuevo Occoro", "Palca", "Pilchaca", "Vilca", "Yauli"),
        "Acobamba" => array("Acobamba", "Andabamba", "Anta", "Caja", "Marcas", "Paucara", "Pomacocha", "Rosario"),
        "Angaraes" => array("Lircay", "Anchonga", "Callanmarca", "Ccochaccasa", "Chincho", "Congalla", "Huanca-Huanca", "Huayllay Grande", "Julcamarca", "San Antonio de Antaparco", "Santo Tomas de Pata", "Secclla"),
        "Castrovirreyna" => array("Castrovirreyna", "Arma", "Aurahua", "Capillas", "Chupamarca", "Cocas", "Huachos", "Huamatambo", "Mollepampa", "San Juan", "Santa Ana", "Tantara", "Ticrapo"),
        "Churcampa" => array("Churcampa", "Anco", "Chinchihuasi", "El Carmen", "La Merced", "Locroja", "Paucarbamba", "San Miguel de Mayocc", "San Pedro de Coris", "Pachamarca"),
        "Huaytará" => array("Huaytara", "Ayavi", "Córdova", "Huayacundo Arma", "Laramarca", "Ocoyo", "Pilpichaca", "Querco", "Quito-Arma", "San Antonio de Cusicancha", "San Francisco de Sangayaico", "San Isidro", "Santiago de Chocorvos", "Santiago de Quirahuara", "Santo Domingo de Capillas", "Tambo"),
        "Tayacaja" => array("Pampas", "Acostambo", "Acraquia", "Ahuaycha", "Colcabamba", "Daniel Hernández", "Huachocolpa", "Huarbamba", "Ñahuimpuquio", "Pazos", "Quishuar", "Salcabamba", "Salcahuasi", "San Marcos de Rocchac", "Surcubamba", "Tintay Puncu")
    ),
    "Huánuco" => array(
        "Huánuco" => array("Huánuco", "Amarilis", "Chinchao", "Churubamba", "Margos", "Quisqui", "San Francisco de Cayran", "San Pedro de Chaulan", "Santa María del Valle", "Yarumayo", "Pillco Marca", "Yacus"),
        "Ambo" => array("Ambo", "Cayna", "Colpas", "Conchamarca", "Huacar", "San Francisco", "San Rafael", "Tomay Kichwa"),
        "Dos de Mayo" => array("La Unión", "Chuquis", "Marías", "Pachas", "Quivilla", "Ripan", "Shunqui", "Sillapata", "Yanas"),
        "Huacaybamba" => array("Huacaybamba", "Canchabamba", "Cochabamba", "Pinra"),
        "Huamalíes" => array("Llata", "Arancay", "Chavín de Pariarca", "Jacas Grande", "Jircan", "Miraflores", "Monzón", "Punchao", "Puños", "Singa", "Tantamayo"),
        "Leoncio Prado" => array("Tingo María", "Rupa-Rupa", "Hermilio Valdizan", "José Crespo y Castillo", "Luyando", "Mariano Damaso Beraun"),
        "Marañón" => array("Huacrachuco", "Cholon", "San Buenaventura"),
        "Pachitea" => array("Panao", "Chaglla", "Molino", "Umari"),
        "Puerto Inca" => array("Puerto Inca", "Codo del Pozuzo", "Honoria", "Tournavista", "Yuyapichis"),
        "Lauricocha" => array("Jesús", "Baños", "Jivia", "Queropalca", "Rondos", "San Francisco de Asís", "San Miguel de Cauri"),
        "Yarowilca" => array("Chavinillo", "Cahuac", "Chacabamba", "Aparicio Pomares", "Jacas Chico", "Obas", "Pampamarca", "Choras")
    ),
    "Ica" => array(
        "Ica" => array("Ica", "La Tinguiña", "Los Aquijes", "Ocucaje", "Pachacutec", "Parcona", "Pueblo Nuevo", "Salas", "San José de Los Molinos", "San Juan Bautista", "Santiago", "Subtanjalla", "Tate", "Yauca del Rosario"),
        "Chincha" => array("Chincha Alta", "Alto Laran", "Chavin", "Chincha Baja", "El Carmen", "Grocio Prado", "Pueblo Nuevo", "San Juan de Yanac", "San Pedro de Huacarpana", "Sunampe", "Tambo de Mora"),
        "Nasca" => array("Nasca", "Changuillo", "El Ingenio", "Marcona", "Vista Alegre"),
        "Palpa" => array("Palpa", "Llipata", "Río Grande", "Santa Cruz", "Tibillo"),
        "Pisco" => array("Pisco", "Huancano", "Humay", "Independencia", "Paracas", "San Andrés", "San Clemente", "Tupac Amaru Inca")
    ),
    "Junín" => array(
        "Huancayo" => array("Huancayo", "Carhuacallanga", "Chacapampa", "Chicche", "Chilca", "Chongos Alto", "Chupuro", "Colca", "Cullhuas", "El Tambo", "Huacrapuquio", "Hualhuas", "Huancan", "Huasicancha", "Huayucachi", "Ingenio", "Pariahuanca", "Pilcomayo", "Pucara", "Quichuay", "Quilcas", "San Agustín", "San Jerónimo de Tunán", "San Pedro de Saño", "Santa Rosa de Ocopa", "Santo Domingo de Acobamba", "Sapallanga", "Sicaya", "Viques"),
        "Concepción" => array("Concepción", "Aco", "Andamarca", "Chambara", "Cochas", "Comas", "Heroínas Toledo", "Manzanares", "Mariscal Castilla", "Matahuasi", "Mito", "Nueve de Julio", "Orcotuna", "San José de Quero", "Santa Rosa de Ocopa"),
        "Chanchamayo" => array("La Merced", "Perene", "Pichanaqui", "San Luis de Shuaro", "San Ramón", "Vitoc"),
        "Jauja" => array("Jauja", "Acolla", "Apata", "Ataura", "Canchayllo", "Curicaca", "El Mantaro", "Huamali", "Huaripampa", "Huertas", "Janjaillo", "Julcán", "Leonor Ordóñez", "Llocllapampa", "Marco", "Masma", "Masma Chicche", "Molinos", "Monobamba", "Muqui", "Muquiyauyo", "Paca", "Paccha", "Pancan", "Parco", "Pomacancha", "Ricran", "San Lorenzo", "San Pedro de Chunan", "Sausa", "Sincos", "Tunan Marca", "Yauli", "Yauyos"),
        "Junín" => array("Junín", "Carhuamayo", "Ondores", "Ulcumayo"),
        "Satipo" => array("Satipo", "Coviriali", "Llaylla", "Mazamari", "Pampa Hermosa", "Pangoa", "Río Negro", "Río Tambo", "Vizcatán del Ene"),
        "Tarma" => array("Tarma", "Acobamba", "Huaricolca", "Huasahuasi", "La Unión", "Palca", "Palcamayo", "San Pedro de Cajas", "Tapo"),
        "Yauli" => array("La Oroya", "Chacapalpa", "Huay-Huay", "Marcapomacocha", "Morococha", "Paccha", "Santa Bárbara de Carhuacayan", "Santa Rosa de Sacco", "Suitucancha", "Yauli"),
        "Chupaca" => array("Chupaca", "Ahuac", "Chongos Bajo", "Huachac", "Huamancaca Chico", "San Juan de Yscos", "San Juan de Jarpa", "Tres de Diciembre", "Yanacancha")
    ),
    "La Libertad" => array(
        "Trujillo" => array("Trujillo", "El Porvenir", "Florencia de Mora", "Huanchaco", "La Esperanza", "Laredo", "Moche", "Poroto", "Salaverry", "Simbal", "Victor Larco Herrera"),
        "Ascope" => array("Ascope", "Chicama", "Chocope", "Magdalena de Cao", "Paiján", "Rázuri", "Santiago de Cao", "Casa Grande"),
        "Bolívar" => array("Bolívar", "Bambamarca", "Condormarca", "Longotea", "Uchumarca", "Ucuncha"),
        "Chepén" => array("Chepén", "Pacanga", "Pueblo Nuevo"),
        "Julcán" => array("Julcán", "Calamarca", "Carabamba", "Huaso"),
        "Otuzco" => array("Otuzco", "Agallpampa", "Charat", "Huaranchal", "La Cuesta", "Mache", "Paranday", "Salpo", "Sinsicap", "Usquil"),
        "Pacasmayo" => array("San Pedro de Lloc", "Guadalupe", "Jequetepeque", "Pacasmayo", "San José"),
        "Pataz" => array("Tayabamba", "Buldibuyo", "Chillia", "Huancaspata", "Huaylillas", "Huayo", "Ongon", "Parcoy", "Pataz", "Pías", "Santiago de Challas", "Taurija", "Urpay"),
        "Sánchez Carrión" => array("Huamachuco", "Chugay", "Cochorco", "Curgos", "Marcabal", "Sanagoran", "Sarin", "Sartimbamba"),
        "Santiago de Chuco" => array("Santiago de Chuco", "Angasmarca", "Cachicadan", "Mollebamba", "Mollepata", "Quiruvilca", "Santa Cruz de Chuca", "Sitabamba"),
        "Gran Chimú" => array("Cascas", "Lucma", "Marmot", "Sayapullo"),
        "Virú" => array("Virú", "Chao", "Guadalupito")
    ),
    "Lambayeque" => array(
        "Chiclayo" => array("Chiclayo", "Chongoyape", "Eten", "Eten Puerto", "José Leonardo Ortiz", "La Victoria", "Lagunas", "Monsefú", "Nueva Arica", "Oyotún", "Picsi", "Pimentel", "Reque", "Santa Rosa", "Saña", "Cayaltí", "Patapo", "Pomalca", "Pucalá", "Tumán"),
        "Ferreñafe" => array("Ferreñafe", "Cañaris", "Incahuasi", "Manuel Antonio Mesones Muro", "Pitipo", "Pueblo Nuevo"),
        "Lambayeque" => array("Lambayeque", "Chochope", "Illimo", "Jayanca", "Mochumi", "Morrope", "Motupe", "Olmos", "Pacora", "Salas", "San José", "Túcume")
    ),
    "Lima" => array(
        "Lima" => array("Lima", "Ancón", "Ate", "Barranco", "Breña", "Carabayllo", "Chaclacayo", "Chorrillos", "Cieneguilla", "Comas", "El Agustino", "Independencia", "Jesús María", "La Molina", "La Victoria", "Lince", "Los Olivos", "Lurigancho", "Lurín", "Magdalena del Mar", "Miraflores", "Pachacamac", "Pucusana", "Pueblo Libre", "Puente Piedra", "Punta Hermosa", "Punta Negra", "Rímac", "San Bartolo", "San Borja", "San Isidro", "San Juan de Lurigancho", "San Juan de Miraflores", "San Luis", "San Martín de Porres", "San Miguel", "Santa Anita", "Santa María del Mar", "Santa Rosa", "Santiago de Surco", "Surquillo", "Villa El Salvador", "Villa María del Triunfo"),
        "Barranca" => array("Barranca", "Paramonga", "Pativilca", "Supe", "Supe Puerto"),
        "Cajatambo" => array("Cajatambo", "Copa", "Gorgor", "Huancapon", "Manas"),
        "Canta" => array("Canta", "Arahuay", "Huamantanga", "Huaros", "Lachaqui", "San Buenaventura", "Santa Rosa de Quives"),
        "Cañete" => array("San Vicente de Cañete", "Asia", "Calango", "Cerro Azul", "Chilca", "Coayllo", "Imperial", "Lunahuana", "Mala", "Nuevo Imperial", "Pacaran", "Quilmana", "San Antonio", "San Luis", "Santa Cruz de Flores", "Zúñiga"),
        "Huaral" => array("Huaral", "Atavillos Alto", "Atavillos Bajo", "Aucallama", "Chancay", "Ihuari", "Lampian", "Pacaraos", "San Miguel de Acos", "Santa Cruz de Andamarca", "Sumbilca", "Veintisiete de Noviembre"),
        "Huarochirí" => array("Matucana", "Antioquia", "Callahuanca", "Carampoma", "Chicla", "Cuenca", "Huachupampa", "Huanza", "Huarochiri", "Lahuaytambo", "Langa", "Laraos", "Mariatana", "Ricardo Palma", "San Andrés de Tupicocha", "San Antonio", "San Bartolomé", "San Damian", "San Juan de Iris", "San Juan de Tantaranche", "San Lorenzo de Quinti", "San Mateo", "San Mateo de Otao", "San Pedro de Casta", "San Pedro de Huancayre", "Sangallaya", "Santa Cruz de Cocachacra", "Santa Eulalia", "Santiago de Anchucaya", "Santiago de Tuna", "Santo Domingo de los Olleros", "Surco"),
        "Huaura" => array("Huacho", "Ambar", "Caleta de Carquin", "Checras", "Hualmay", "Huaura", "Leoncio Prado", "Paccho", "Santa Leonor", "Santa María", "Sayan", "Vegueta"),
        "Oyón" => array("Oyón", "Andajes", "Caujul", "Cochamarca", "Navan", "Pachangara"),
        "Yauyos" => array("Yauyos", "Alis", "Allauca", "Ayaviri", "Azángaro", "Cacra", "Carania", "Catahuasi", "Chocos", "Cochas", "Colonia", "Hongos", "Huampara", "Huancaya", "Huangascar", "Huantan", "Huañec", "Laraos", "Lincha", "Madean", "Miraflores", "Omas", "Putinza", "Quinches", "Quinocay", "San Joaquín", "San Pedro de Pilas", "Tanta", "Tauripampa", "Tomas", "Tupe", "Viñac", "Vitis")
    ),
    "Loreto" => array(
        "Maynas" => array("Iquitos", "Alto Nanay", "Fernando Lores", "Indiana", "Las Amazonas", "Mazán", "Napo", "Punchana", "Putumayo", "Torres Causana", "Belén", "San Juan Bautista"),
        "Alto Amazonas" => array("Yurimaguas", "Balsapuerto", "Jeberos", "Lagunas", "Santa Cruz", "Teniente Cesar López Rojas"),
        "Loreto" => array("Nauta", "Parinari", "Tigre", "Trompeteros", "Urarinas"),
        "Mariscal Ramón Castilla" => array("Ramón Castilla", "Pebas", "Yavari", "San Pablo"),
        "Requena" => array("Requena", "Alto Tapiche", "Capelo", "Emilio San Martín", "Maquia", "Puinahua", "Saquena", "Soplin", "Tapiche", "Jenaro Herrera", "Yaquerana"),
        "Ucayali" => array("Contamana", "Inahuaya", "Padre Márquez", "Pampa Hermosa", "Sarayacu", "Vargas Guerra"),
        "Datem del Marañón" => array("Barranca", "Cahuapanas", "Manseriche", "Morona", "Pastaza", "Andoas")
    ),
    "Madre de Dios" => array(
        "Tambopata" => array("Tambopata", "Inambari", "Las Piedras", "Laberinto"),
        "Manu" => array("Manu", "Fitzcarrald", "Madre de Dios", "Huepetuhe"),
        "Tahuamanu" => array("Iñapari", "Iberia", "Tahuamanu")
    ),
    "Moquegua" => array(
        "Mariscal Nieto" => array("Moquegua", "Carumas", "Cuchumbaya", "Samegua", "San Cristóbal", "Torata"),
        "General Sánchez Cerro" => array("Omate", "Chojata", "Coalaque", "Ichuña", "La Capilla", "Lloque", "Matalaque", "Puquina", "Quinistaquillas", "Ubinas", "Yunga"),
        "Ilo" => array("Ilo", "El Algarrobal", "Pacocha")
    ),
    "Pasco" => array(
        "Pasco" => array("Chaupimarca", "Huachon", "Huariaca", "Huayllay", "Ninacaca", "Pallanchacra", "Paucartambo", "San Francisco de Asís de Yarusyacan", "Simon Bolívar", "Ticlacayan", "Tinyahuarco", "Vicco", "Yanacancha"),
        "Daniel Alcides Carrión" => array("Yanahuanca", "Chacayan", "Goyllarisquizga", "Paucar", "San Pedro de Pillao", "Santa Ana de Tusi", "Tapuc", "Vilcabamba"),
        "Oxapampa" => array("Oxapampa", "Chontabamba", "Huancabamba", "Palcazu", "Pozuzo", "Puerto Bermúdez", "Villa Rica")
    ),
    "Piura" => array(
        "Piura" => array("Piura", "Castilla", "Catacaos", "Cura Mori", "El Tallan", "La Arena", "La Unión", "Las Lomas", "Tambo Grande"),
        "Ayabaca" => array("Ayabaca", "Frias", "Jilili", "Lagunas", "Montero", "Pacaipampa", "Paimas", "Sapillica", "Sicchez", "Suyo"),
        "Huancabamba" => array("Huancabamba", "Canchaque", "El Carmen de la Frontera", "Huarmaca", "Lalaquiz", "San Miguel de El Faique", "Sondor", "Sondorillo"),
        "Morropón" => array("Chulucanas", "Buenos Aires", "Chalaco", "La Matanza", "Morropón", "Salitral", "San Juan de Bigote", "Santa Catalina de Mossa", "Santo Domingo", "Yamango"),
        "Paita" => array("Paita", "Amotape", "Arenal", "Colan", "La Huaca", "Tamarindo", "Vichayal"),
        "Sullana" => array("Sullana", "Bellavista", "Ignacio Escudero", "Lancones", "Marcavelica", "Miguel Checa", "Querecotillo", "Salitral"),
        "Talara" => array("Talara", "La Brea", "Lobitos", "Los Organos", "Mancora", "Pariñas"),
        "Sechura" => array("Sechura", "Bellavista de la Unión", "Bernal", "Cristo Nos Valga", "Vice", "Rinconada Llicuar")
    ),
    "Puno" => array(
        "Puno" => array("Puno", "Acora", "Amantani", "Atuncolla", "Capachica", "Chucuito", "Coata", "Huata", "Mañazo", "Paucarcolla", "Pichacani", "Platería", "San Antonio", "Tiquillaca", "Vilque"),
        "Azángaro" => array("Azángaro", "Achaya", "Arapa", "Asillo", "Caminaca", "Chupa", "José Domingo Choquehuanca", "Muñani", "Potoni", "Saman", "San Anton", "San José", "San Juan de Salinas", "Santiago de Pupuja", "Tirapata"),
        "Carabaya" => array("Macusani", "Ajoyani", "Ayapata", "Coasa", "Corani", "Crucero", "Ituata", "Ollachea", "San Gaban", "Usicayos"),
        "Chucuito" => array("Juli", "Desaguadero", "Huacullani", "Kelluyo", "Pisacoma", "Pomata", "Zepita"),
        "El Collao" => array("Ilave", "Capazo", "Pilcuyo", "Santa Rosa", "Conduriri"),
        "Huancané" => array("Huancané", "Cojata", "Huatasani", "Inchupalla", "Pusi", "Rosaspata", "Taraco", "Vilque Chico"),
        "Lampa" => array("Lampa", "Cabanilla", "Calapuja", "Nicasio", "Ocuviri", "Palca", "Paratia", "Pucara", "Santa Lucia", "Vilavila"),
        "Melgar" => array("Ayaviri", "Antauta", "Cupi", "Llalli", "Macari", "Nuñoa", "Orurillo", "Santa Rosa", "Umachiri"),
        "Moho" => array("Moho", "Conima", "Huayrapata", "Tilali"),
        "San Antonio de Putina" => array("Putina", "Ananea", "Pedro Vilca Apaza", "Quilcapuncu", "Sina"),
        "San Román" => array("Juliaca", "Cabana", "Cabanillas", "Caracoto"),
        "Sandia" => array("Sandia", "Cuyocuyo", "Limbani", "Patambuco", "Phara", "Quiaca", "San Juan del Oro", "Yanahuaya"),
        "Yunguyo" => array("Yunguyo", "Anapia", "Copani", "Cuturapi", "Ollaraya", "Tinicachi", "Unicachi")
    ),
    "San Martín" => array(
        "Moyobamba" => array("Moyobamba", "Calzada", "Habana", "Jepelacio", "Soritor", "Yantalo"),
        "Bellavista" => array("Bellavista", "Alto Biavo", "Bajo Biavo", "Huallaga", "San Pablo", "San Rafael"),
        "El Dorado" => array("San José de Sisa", "Agua Blanca", "San Martín", "Santa Rosa", "Shatoja"),
        "Huallaga" => array("Saposoa", "Alto Saposoa", "El Eslabón", "Piscoyacu", "Sacanche", "Tingo de Saposoa"),
        "Lamas" => array("Lamas", "Alonso de Alvarado", "Barranquita", "Caynarachi", "Cuñumbuqui", "Pinto Recodo", "Rumisapa", "San Roque de Cumbaza", "Shanao", "Tabalosos", "Zapatero"),
        "Mariscal Cáceres" => array("Juanjuí", "Campanilla", "Huicungo", "Pachiza", "Pajarillo"),
        "Picota" => array("Picota", "Buenos Aires", "Caspisapa", "Pilluana", "Pucacaca", "San Cristóbal", "San Hilarión", "Shamboyaca", "Tingo de Ponasa", "Tres Unidos"),
        "Rioja" => array("Rioja", "Awajun", "Elías Soplin Vargas", "Nueva Cajamarca", "Pardo Miguel", "Posic", "San Fernando", "Yorongos", "Yuracyacu"),
        "San Martín" => array("Tarapoto", "Alberto Leveau", "Cacatachi", "Chazuta", "Chipurana", "El Porvenir", "Huimbayoc", "Juan Guerra", "La Banda de Shilcayo", "Morales", "Papaplaya", "San Antonio", "Sauce", "Shapaja"),
        "Tocache" => array("Tocache", "Nuevo Progreso", "Polvora", "Shunte", "Uchiza")
    ),
    "Tacna" => array(
        "Tacna" => array("Tacna", "Alto de la Alianza", "Calana", "Ciudad Nueva", "Inclan", "Pachia", "Palca", "Pocollay", "Sama", "Coronel Gregorio Albarracín Lanchipa"),
        "Candarave" => array("Candarave", "Cairani", "Camilaca", "Curibaya", "Huanuara", "Quilahuani"),
        "Jorge Basadre" => array("Locumba", "Ilabaya", "Ite"),
        "Tarata" => array("Tarata", "Héroes Albarracín", "Estique", "Estique-Pampa", "Sitajara", "Susapaya", "Ticaco")
    ),
    "Tumbes" => array(
        "Tumbes" => array("Tumbes", "Corrales", "La Cruz", "Pampas de Hospital", "San Jacinto", "San Juan de la Virgen"),
        "Contralmirante Villar" => array("Zorritos", "Casitas", "Canoas de Punta Sal"),
        "Zarumilla" => array("Zarumilla", "Aguas Verdes", "Matapalo", "Papayal")
    ),
    "Ucayali" => array(
        "Coronel Portillo" => array("Calleria", "Campoverde", "Iparia", "Masisea", "Yarinacocha", "Nueva Requena"),
        "Atalaya" => array("Atalaya", "Raymondi", "Sepahua", "Tahuania"),
        "Padre Abad" => array("Padre Abad", "Irazola", "Curimana", "Neshuya"),
        "Purús" => array("Purús")
    )
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrito de Compras - SaludPerfecta</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ========================================== */
/* VARIABLES GLOBALES FUTURISTAS */
/* ========================================== */
:root {
  --neon-cyan: #00ffff;
  --neon-pink: #f0e2f0ff;
  --neon-green: #39ff14;
  --neon-orange: #ff6600;
  --dark-bg: #0a0a0a;
  --darker-bg: #050505;
  --card-bg: #0f0f19;
  --glass-bg: #ffffff0d;
  --text-primary: #ffffff;
  --text-secondary: #cccccc;
  --text-muted: #888888;
  --gradient-primary: linear-gradient(135deg, #00ffff 0%, #ff00ff 100%);
  --gradient-secondary: linear-gradient(45deg, #39ff14 0%, #00ffff 50%, #ff00ff 100%);
  --gradient-dark: linear-gradient(135deg, #0a0a0a 0%, #1a0a1a 100%);
  --shadow-neon: 0 0 20px rgba(255, 255, 255, 0.3);
  --shadow-pink: 0 0 20px rgba(255, 255, 255, 0.3);
  --shadow-green: 0 0 20px rgba(255, 255, 255, 0.3);
  --font-tech: 'Orbitron', monospace;
  --font-body: 'Rajdhani', sans-serif;
}

/* ========================================== */
/* MODO CLARO */
/* ========================================== */
body.light-mode {
  --dark-bg: #f0f2f5;
  --darker-bg: #e4e6ef;
  --card-bg: #ffffff;
  --glass-bg: #0000000d;
  --text-primary: #2d3748;
  --text-secondary: #4a5568;
  --text-muted: #718096;
  --neon-cyan: #008489;
  --neon-pink: #000000ff;
  --neon-green: #38a169;
  --neon-orange: #ed8936;
  --gradient-primary: linear-gradient(135deg, #008489 0%, #d53f8c 100%);
  --gradient-secondary: linear-gradient(45deg, #38a169 0%, #008489 50%, #d53f8c 100%);
  --gradient-dark: linear-gradient(135deg, #f0f2f5 0%, #e4e6ef 100%);
  --shadow-neon: 0 0 20px rgba(0, 0, 0, 0.3);
  --shadow-pink: 0 0 20px rgba(0, 0, 0, 0.3);
  --shadow-green: 0 0 20px rgba(0, 0, 0, 0.3);
}

/* ========================================== */
/* RESET Y BASE STYLES */
/* ========================================== */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

*::before,
*::after {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: var(--font-body);
  background: var(--dark-bg);
  color: var(--text-primary);
  overflow-x: hidden;
  position: relative;
  font-weight: 400;
  transition: background-color 0.5s ease, color 0.5s ease;
}

/* ========================================== */
/* FONDO ANIMADO CON PARTÍCULAS */
/* ========================================== */
body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(circle at 20% 20%, #00ffff1a 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, #ff00ff1a 0%, transparent 50%),
    radial-gradient(circle at 40% 60%, #39ff140d 0%, transparent 50%);
  z-index: -2;
  animation: backgroundShift 20s ease-in-out infinite;
}

body.light-mode::before {
  background: 
    radial-gradient(circle at 20% 20%, #0084891a 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, #d53f8c1a 0%, transparent 50%),
    radial-gradient(circle at 40% 60%, #38a1690d 0%, transparent 50%);
}

@keyframes backgroundShift {
  0%, 100% { transform: scale(1) rotate(0deg); }
  50% { transform: scale(1.1) rotate(180deg); }
}

/* Partículas flotantes */
.particles {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -1;
  pointer-events: none;
}

.particle {
  position: absolute;
  width: 2px;
  height: 2px;
  background: var(--neon-cyan);
  border-radius: 50%;
  animation: float 8s linear infinite;
}

.particle:nth-child(2n) { background: var(--neon-pink); }
.particle:nth-child(3n) { background: var(--neon-green); }

@keyframes float {
  0% {
    transform: translateY(100vh) translateX(0) scale(0);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-100vh) translateX(100px) scale(1);
    opacity: 0;
  }
}

/* ========================================== */
/* HEADER CYBERPUNK */
/* ========================================== */
header {
  position: fixed;
  top: 0;
  width: 100%;
  background: #0a0a0ae6;
  backdrop-filter: blur(20px);
  border-bottom: 2px solid transparent;
  border-image: var(--gradient-primary) 1;
  z-index: 1000;
  transition: all 0.3s ease;
}

body.light-mode header {
  background: #f0f2f5e6;
}

header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--gradient-primary);
  opacity: 0.1;
  z-index: -1;
}

.header-inner {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 30px;
}

.logo {
  font-family: var(--font-tech);
  font-size: 2.5rem;
  font-weight: 900;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 0 20px #00ffff80;
  position: relative;
  transition: all 0.3s ease;
  cursor: pointer;
}

.logo:hover {
  transform: scale(1.05);
  filter: drop-shadow(0 0 10px var(--neon-cyan));
}

.logo::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  width: 100%;
  height: 2px;
  background: var(--gradient-primary);
  transform: scaleX(0);
  transition: transform 0.3s ease;
  z-index: -1;
}

.logo:hover::after {
  transform: scaleX(1);
}

.nav-menu {
  display: flex;
  gap: 40px;
  list-style: none;
}

.nav-menu a {
  color: var(--text-primary);
  text-decoration: none;
  font-weight: 600;
  font-size: 1.2rem;
  position: relative;
  transition: all 0.3s ease;
  padding: 10px 0;
}

.nav-menu a::before {
  content: '';
  position: absolute;
  top: 0;
  left: -10px;
  width: calc(100% + 20px);
  height: 100%;
  background: var(--gradient-primary);
  opacity: 0;
  transform: skewX(-15deg) scaleX(0);
  transition: all 0.3s ease;
  z-index: -1;
}

.nav-menu a:hover::before {
  opacity: 0.2;
  transform: skewX(-15deg) scaleX(1);
}

.nav-menu a:hover {
  color: var(--neon-cyan);
  text-shadow: 0 0 10px var(--neon-cyan);
}

.header-tools {
  display: flex;
  align-items: center;
  gap: 20px;
}

.theme-toggle {
  background: var(--card-bg);
  border: 2px solid var(--neon-cyan);
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: var(--neon-cyan);
  font-size: 1.5rem;
}

.theme-toggle:hover {
  background: var(--neon-cyan);
  color: var(--dark-bg);
  box-shadow: 0 0 20px var(--neon-cyan);
  transform: rotate(30deg);
}

.theme-toggle:active {
  transform: scale(0.95) rotate(30deg);
}

.cart-icon-container {
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cart-icon {
  font-size: 2rem;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.cart-icon:hover {
  color: var(--neon-cyan);
  transform: scale(1.1);
  filter: drop-shadow(0 0 10px var(--neon-cyan));
}

.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: var(--gradient-primary);
  color: var(--text-primary);
  font-size: 0.8rem;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse-badge 2s infinite;
  border: 2px solid var(--dark-bg);
}

body.light-mode .cart-badge {
  border: 2px solid var(--card-bg);
}

@keyframes pulse-badge {
  0% { transform: scale(1); box-shadow: 0 0 0 0 #00ffffb3; }
  70% { transform: scale(1); box-shadow: 0 0 0 10px #00ffff00; }
  100% { transform: scale(1); box-shadow: 0 0 0 0 #00ffff00; }
}

/* ========================================== */
/* MAIN CONTENT */
/* ========================================== */
main {
  margin-top: 100px;
  min-height: calc(100vh - 100px);
  padding: 40px 20px;
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}

.page-title {
  font-family: var(--font-tech);
  font-size: 4rem;
  font-weight: 900;
  text-align: center;
  margin-bottom: 60px;
  background: var(--gradient-secondary);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  position: relative;
  animation: titleGlow 3s ease-in-out infinite alternate;
}

@keyframes titleGlow {
  0% { 
    filter: drop-shadow(0 0 20px #00ffff80);
    transform: scale(1);
  }
  100% { 
    filter: drop-shadow(0 0 40px #ff00ffcc);
    transform: scale(1.02);
  }
}

.page-title::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, #00ffff1a 0%, transparent 70%);
  transform: translate(-50%, -50%);
  animation: rotate 10s linear infinite;
  z-index: -1;
}

@keyframes rotate {
  0% { transform: translate(-50%, -50%) rotate(0deg); }
  100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* ========================================== */
/* CARRITO VACÍO */
/* ========================================== */
.empty-cart {
  text-align: center;
  padding: 100px 40px;
  background: var(--card-bg);
  border-radius: 30px;
  border: 2px solid transparent;
  background-clip: padding-box;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(20px);
  margin: 40px 0;
}

.empty-cart::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--gradient-primary);
  opacity: 0.1;
  z-index: -1;
}

.empty-cart-icon {
  font-size: 8rem;
  color: var(--neon-cyan);
  margin-bottom: 30px;
  animation: float-icon 4s ease-in-out infinite;
  filter: drop-shadow(0 0 20px var(--neon-cyan));
}

@keyframes float-icon {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

.empty-cart p {
  font-size: 1.5rem;
  color: var(--text-secondary);
  margin-bottom: 40px;
  font-weight: 300;
}

/* ========================================== */
/* BOTONES FUTURISTAS SIN COLORES */
/* ========================================== */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 15px 30px;
  background: transparent;
  color: var(--text-primary);
  border: 2px solid var(--text-primary);
  border-radius: 15px;
  font-family: var(--font-body);
  font-weight: 600;
  font-size: 1.1rem;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  text-decoration: none;
  backdrop-filter: blur(10px);
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.1);
}

.btn:active {
  transform: translateY(0) scale(0.98);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.btn-danger {
  border-color: var(--text-primary);
  color: var(--text-primary);
}

.btn-danger:hover {
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.1);
}

.btn-success {
  border-color: var(--text-primary);
  color: var(--text-primary);
}

.btn-success:hover {
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.1);
}

/* ========================================== */
/* CART CONTAINER */
/* ========================================== */
.cart-container {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 40px;
  margin-bottom: 60px;
  align-items: start;
}

/* ========================================== */
/* TABLA DEL CARRITO CYBERPUNK */
/* ========================================== */
.cart-table-container {
  background: var(--card-bg);
  border-radius: 25px;
  padding: 30px;
  border: 2px solid transparent;
  background-clip: padding-box;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(20px);
  height: fit-content;
}

.cart-table-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--gradient-primary);
  opacity: 0.05;
  z-index: -1;
}

.cart-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 15px;
}

.cart-table thead th {
  color: var(--neon-cyan);
  font-family: var(--font-tech);
  font-weight: 700;
  font-size: 1.1rem;
  padding: 20px 15px;
  text-align: left;
  border-bottom: 2px solid var(--neon-cyan);
  position: relative;
}

.cart-table thead th::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 100%;
  height: 2px;
  background: var(--gradient-primary);
  animation: neon-line 2s ease-in-out infinite alternate;
}

@keyframes neon-line {
  0% { opacity: 0.5; }
  100% { opacity: 1; box-shadow: 0 0 10px var(--neon-cyan); }
}

.cart-item-row {
  background: #ffffff05;
  border-radius: 15px;
  position: relative;
  transition: all 0.3s ease;
  animation: slideInFromLeft 0.6s ease;
}

@keyframes slideInFromLeft {
  0% {
    opacity: 0;
    transform: translateX(-50px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

.cart-item-row:hover {
  background: #00ffff0d;
  transform: translateX(5px);
  box-shadow: 0 0 20px #00ffff33;
}

.cart-item-row td {
  padding: 20px 15px;
  border: none;
  vertical-align: middle;
}

.cart-item-row td:first-child {
  border-radius: 15px 0 0 15px;
}

.cart-item-row td:last-child {
  border-radius: 0 15px 15px 0;
}

.cart-product {
  display: flex;
  align-items: center;
  gap: 20px;
}

.cart-product-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 15px;
  border: 2px solid var(--neon-cyan);
  box-shadow: 0 0 15px #00ffff4d;
  transition: all 0.3s ease;
}

.cart-product-img:hover {
  transform: scale(1.1) rotate(5deg);
  box-shadow: 0 0 25px #00ffff99;
}

.cart-product-info h4 {
  color: var(--text-primary);
  font-weight: 600;
  margin-bottom: 5px;
  font-size: 1.1rem;
}

.cart-product-category {
  color: var(--neon-green);
  font-size: 0.9rem;
  padding: 2px 8px;
  background: #39ff141a;
  border-radius: 8px;
  display: inline-block;
}

.cart-product-price {
  color: var(--neon-cyan);
  font-weight: 700;
  font-size: 1.2rem;
  font-family: var(--font-tech);
}

.cart-product-subtotal {
  color: var(--neon-pink);
  font-weight: 900;
  font-size: 1.3rem;
  font-family: var(--font-tech);
  text-shadow: #fff;
}

/* ========================================== */
/* CONTROLES DE CANTIDAD FUTURISTAS */
/* ========================================== */
.quantity-control {
  display: flex;
  align-items: center;
  gap: 15px;
  background: #ffffff08;
  padding: 10px 15px;
  border-radius: 25px;
  border: 1px solid #00ffff4d;
}

.quantity-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid var(--text-primary);
  background: transparent;
  color: var(--text-primary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  font-weight: 700;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.quantity-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  box-shadow: var(--shadow-neon);
  transform: scale(1.1);
}

.quantity-btn:active {
  transform: scale(0.95);
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
}

.quantity-input {
  width: 60px;
  text-align: center;
  background: transparent;
  border: none;
  color: var(--text-primary);
  font-size: 1.2rem;
  font-weight: 600;
  font-family: var(--font-tech);
}

.quantity-input:focus {
  outline: none;
  color: var(--neon-cyan);
  text-shadow: 0 0 10px var(--neon-cyan);
}

.update-form, .delete-form {
  display: inline;
}

.update-btn {
  margin-top: 10px;
  padding: 8px 16px;
  font-size: 0.8rem;
  border-radius: 20px;
}

.update-btn:active {
  transform: scale(0.95);
}

/* ========================================== */
/* RESUMEN DEL CARRITO */
/* ========================================== */
.cart-summary {
  background: var(--card-bg);
  border-radius: 25px;
  padding: 30px;
  border: 2px solid transparent;
  background-clip: padding-box;
  position: sticky;
  top: 100px;
  height: fit-content;
  backdrop-filter: blur(20px);
  overflow: hidden;
  align-self: start;
}

.cart-summary::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--gradient-primary);
  opacity: 0.08;
  z-index: -1;
  border-radius: 25px;
}

.summary-title {
  font-family: var(--font-tech);
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--neon-cyan);
  margin-bottom: 30px;
  text-align: center;
  position: relative;
}

.summary-title::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 2px;
  background: var(--gradient-primary);
  animation: pulse-line 2s ease-in-out infinite;
}

@keyframes pulse-line {
  0%, 100% { opacity: 0.5; transform: translateX(-50%) scaleX(1); }
  50% { opacity: 1; transform: translateX(-50%) scaleX(1.2); }
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 15px 0;
  border-bottom: 1px solid #ffffff1a;
  font-size: 1.1rem;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item span:first-child {
  color: var(--text-secondary);
  font-weight: 400;
}

.summary-item span:last-child {
  color: var(--text-primary);
  font-weight: 600;
  font-family: var(--font-tech);
}

.summary-total {
  font-size: 1.6rem;
  font-weight: 900;
  margin: 30px 0;
  padding: 20px;
  background: #00ffff0d;
  border-radius: 15px;
  border: 2px solid var(--neon-cyan);
  color: var(--neon-cyan);
  text-align: center;
  font-family: var(--font-tech);
  animation: total-glow 3s ease-in-out infinite;
}

@keyframes total-glow {
  0%, 100% { box-shadow: 0 0 20px #00ffff4d; }
  50% { box-shadow: 0 0 40px #00ffff99; }
}

.free-shipping {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--neon-green);
  margin: 20px 0;
  padding: 15px;
  background-color: #39ff141a;
  border-radius: 15px;
  font-weight: 600;
}

.free-shipping i {
  font-size: 1.5rem;
}

.shipping-notice {
  margin: 15px 0;
  padding: 12px;
  font-size: 0.95rem;
  color: var(--text-muted);
  background-color: #7f8c8d1a;
  border-radius: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.shipping-notice i {
  color: var(--neon-orange);
}

/* ========================================== */
/* FORMULARIO DE CHECKOUT */
/* ========================================== */
.checkout-form {
  background: var(--card-bg);
  border-radius: 25px;
  padding: 40px;
  margin-top: 60px;
  border: 2px solid transparent;
  background-clip: padding-box;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(20px);
  display: none;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.5s ease;
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
}

.checkout-form.visible {
  display: block;
  opacity: 1;
  transform: translateY(0);
}

.checkout-form::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--gradient-secondary);
  opacity: 0.03;
  z-index: -1;
}

.form-title {
  font-family: var(--font-tech);
  font-size: 2.2rem;
  font-weight: 700;
  color: var(--neon-pink);
  margin-bottom: 40px;
  text-align: center;
  animation: form-title-glow 4s ease-in-out infinite alternate;
}

@keyframes form-title-glow {
  0% { 
    text-shadow: 0 0 20px var(--neon-pink);
    transform: scale(1);
  }
  100% { 
    text-shadow: 0 0 30px var(--neon-pink);
    transform: scale(1.02);
  }
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 25px;
  margin-bottom: 30px;
}

.form-group {
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 10px;
  font-weight: 600;
  color: #626262ff;
  font-size: 1rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 15px 20px;
  background: #ffffff0d;
  border: 2px solid #5c5e5eff;
  border-radius: 15px;
  color: var(--text-primary);
  font-size: 1rem;
  font-family: var(--font-body);
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--neon-cyan);
  box-shadow: 0 0 20px #00ffff4d;
  transform: translateY(-2px);
}

.form-group input::placeholder {
  color: var(--text-muted);
}

.form-group-full {
  grid-column: 1 / -1;
}

/* ========================================== */
/* MÉTODOS DE PAGO FUTURISTAS */
/* ========================================== */
.payment-methods {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin: 30px 0;
}

.payment-mode-title {
  font-family: var(--font-tech);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--neon-cyan);
  margin-bottom: 15px;
  text-align: center;
  position: relative;
}

.payment-mode-title::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 2px;
  background: var(--gradient-primary);
}

.payment-mode {
  background: #ffffff05;
  border: 2px solid var(--text-primary);
  border-radius: 20px;
  padding: 25px;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.payment-mode:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.1);
}

.payment-options {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.payment-method {
  background: #ffffff03;
  border: 2px solid var(--text-primary);
  border-radius: 15px;
  padding: 20px 15px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.payment-method:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.1);
}

.payment-method.selected {
  transform: scale(1.02);
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.15);
}

.payment-method:active {
  transform: scale(0.98);
}

.payment-method i {
  font-size: 2.5rem;
  margin-bottom: 10px;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.payment-method:hover i {
  transform: scale(1.1);
}

.payment-method div {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

/* ========================================== */
/* BOTÓN DE CHECKOUT ÉPICO */
/* ========================================== */
.btn-checkout {
  width: 100%;
  padding: 25px 40px;
  background: #6a6b6aff;
  color: #ffffffff;
  border: 1px solid var(--text-primary);
  border-radius: 20px;
  font-family: var(--font-tech);
  font-size: 1.4rem;
  font-weight: 900;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
  letter-spacing: 2px;
  transition: all 0.3s ease;
}

.btn-checkout::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: 0.5s;
}

.btn-checkout:hover::before {
  left: 100%;
}

.btn-checkout:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-neon);
  background: rgba(0, 0, 0, 1);
}

.btn-checkout:active {
  transform: scale(0.98);
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}

/* ========================================== */
/* TOAST NOTIFICATIONS CYBERPUNK */
/* ========================================== */
.toast {
  position: fixed;
  bottom: 30px;
  right: 30px;
  padding: 20px 25px;
  background: var(--card-bg);
  border: 2px solid var(--neon-green);
  border-radius: 15px;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 15px;
  z-index: 10000;
  opacity: 0;
  transform: translateX(400px);
  transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  backdrop-filter: blur(20px);
  box-shadow: 0 0 30px #39ff144d;
  max-width: 400px;
}

.toast.show {
  opacity: 1;
  transform: translateX(0);
}

.toast.error {
  border-color: var(--text-primary);
  box-shadow: var(--shadow-neon);
}

.toast i {
  font-size: 1.5rem;
  color: var(--neon-green);
}

.toast.error i {
  color: var(--text-primary);
}

.toast-message {
  flex: 1;
  font-weight: 500;
}

.toast-close {
  background: none;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  font-size: 1.2rem;
  opacity: 0.7;
  transition: all 0.3s ease;
}

.toast-close:hover {
  opacity: 1;
}

.toast-close:active {
  transform: scale(0.9);
}

/* ========================================== */
/* FOOTER CYBERPUNK */
/* ========================================== */
footer {
  background: var(--darker-bg);
  border-top: 2px solid transparent;
  border-image: var(--gradient-primary) 1;
  padding: 60px 0 20px;
  margin-top: 80px;
  position: relative;
  overflow: hidden;
}

footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--gradient-primary);
  opacity: 0.03;
  z-index: -1;
}

.footer-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 40px;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 30px;
}

.footer-col h4 {
  font-family: var(--font-tech);
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--neon-cyan);
  margin-bottom: 25px;
  position: relative;
  padding-bottom: 10px;
}

.footer-col h4::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 50px;
  height: 2px;
  background: var(--gradient-primary);
  animation: footer-line-pulse 2s ease-in-out infinite alternate;
}

@keyframes footer-line-pulse {
  0% { width: 50px; }
  100% { width: 100px; }
}

.footer-col ul {
  list-style: none;
}

.footer-col ul li {
  margin-bottom: 15px;
}

.footer-col ul li a {
  color: var(--text-secondary);
  text-decoration: none;
  transition: all 0.3s ease;
  display: inline-block;
  position: relative;
}

.footer-col ul li a::before {
  content: '>';
  color: var(--neon-cyan);
  margin-right: 10px;
  opacity: 0;
  transition: all 0.3s ease;
  transform: translateX(-10px);
}

.footer-col ul li a:hover::before {
  opacity: 1;
  transform: translateX(0);
}

.footer-col ul li a:hover {
  color: var(--neon-cyan);
  transform: translateX(5px);
}

.footer-col p {
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 20px;
}

.footer-social {
  display: flex;
  gap: 15px;
  margin-top: 20px;
}

.footer-social a {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #ffffff0d;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-primary);
  font-size: 1.5rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  border: 2px solid var(--text-primary);
}

.footer-social a:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.1);
}

.footer-newsletter input {
  width: 100%;
  padding: 15px 20px;
  background: #ffffff0d;
  border: 2px solid var(--text-primary);
  border-radius: 15px;
  color: var(--text-primary);
  font-size: 1rem;
  margin-bottom: 15px;
  transition: all 0.3s ease;
}

.footer-newsletter input:focus {
  outline: none;
  box-shadow: var(--shadow-neon);
  transform: translateY(-2px);
}

.footer-newsletter button {
  width: 100%;
  padding: 15px 20px;
  background: transparent;
  color: var(--text-primary);
  border: 2px solid var(--text-primary);
  border-radius: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.footer-newsletter button:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-neon);
  background: rgba(255, 255, 255, 0.1);
}

.footer-newsletter button:active {
  transform: scale(0.98);
}

.footer-bottom {
  text-align: center;
  padding: 30px 0 0;
  margin-top: 60px;
  border-top: 1px solid #ffffff1a;
  max-width: 1400px;
  margin-left: auto;
  margin-right: auto;
  color: var(--text-muted);
  font-size: 0.9rem;
}

/* ========================================== */
/* RESPONSIVE DESIGN */
/* ========================================== */
@media (max-width: 1200px) {
  .cart-container {
    grid-template-columns: 1fr;
  }
  
  .cart-summary {
    position: static;
    margin-top: 40px;
  }
  
  .footer-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .header-inner {
    flex-direction: column;
    gap: 20px;
    padding: 15px;
  }
  
  .nav-menu {
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .page-title {
    font-size: 2.5rem;
  }
  
  .cart-table thead {
    display: none;
  }
  
  .cart-item-row {
    display: flex;
    flex-direction: column;
    padding: 20px;
    margin-bottom: 20px;
  }
  
  .cart-item-row td {
    padding: 10px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .cart-item-row td::before {
    content: attr(data-label);
    font-weight: 700;
    color: var(--neon-cyan);
    margin-right: 20px;
  }
  
  .cart-product {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .payment-methods {
    grid-template-columns: 1fr;
  }
  
  .footer-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .header-tools {
    flex-direction: column;
    gap: 15px;
  }
  
  .nav-menu {
    gap: 10px;
  }
  
  .nav-menu a {
    font-size: 1rem;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .cart-table-container,
  .cart-summary,
  .checkout-form {
    padding: 20px;
  }
  
  .btn {
    padding: 12px 20px;
    font-size: 1rem;
  }
  
  .toast {
    right: 15px;
    left: 15px;
    max-width: none;
  }
}
</style>
</head>
<body>
  <!-- Partículas flotantes -->
  <div class="particles" id="particles"></div>

  <!-- Header -->
  <header>
    <div class="header-inner">
      <a href="index.php" class="logo">
        <i class="fas fa-heartbeat"></i>
        SaludPerfecta
      </a>
      
      <nav class="nav-menu">
        <a href="index.php">Inicio</a>
        <a href="index.php#productos">Productos</a>
        <a href="index.php#categorias">Categorías</a>
        <a href="index.php#contacto">Contacto</a>
      </nav>
      
      <div class="header-tools">
        <div class="theme-toggle" id="theme-toggle">
          <i class="fas fa-moon"></i>
        </div>
        
        <div class="cart-icon-container">
          <a href="carrito.php" class="cart-icon">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-badge"><?= $count ?></span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main>
    <h1 class="page-title">Carrito de Compras</h1>

    <?php if (empty($carrito)): ?>
      <div class="empty-cart">
        <div class="empty-cart-icon">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <p>Tu carrito está vacío</p>
        <a href="index.php" class="btn">
          <i class="fas fa-arrow-left"></i> Seguir Comprando
        </a>
      </div>
    <?php else: ?>
      <div class="cart-container">
        <div class="cart-table-container">
          <table class="cart-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($carrito as $id => $item): ?>
                <tr class="cart-item-row">
                  <td data-label="Producto">
                    <div class="cart-product">
                      <img src="<?= $item['imagen'] ?>" alt="<?= $item['nombre'] ?>" class="cart-product-img">
                      <div class="cart-product-info">
                        <h4><?= $item['nombre'] ?></h4>
                        <div class="cart-product-category"><?= $item['categoria'] ?? 'Suplemento' ?></div>
                      </div>
                    </div>
                  </td>
                  <td data-label="Precio" class="cart-product-price">S/ <?= number_format($item['precio'], 2) ?></td>
                  <td data-label="Cantidad">
                    <form method="POST" class="update-form">
                      <input type="hidden" name="id_producto" value="<?= $id ?>">
                      <div class="quantity-control">
                        <button type="button" class="quantity-btn minus" data-id="<?= $id ?>">-</button>
                        <input type="number" name="cantidad" value="<?= $item['cantidad'] ?>" min="1" class="quantity-input" data-id="<?= $id ?>" data-price="<?= $item['precio'] ?>">
                        <button type="button" class="quantity-btn plus" data-id="<?= $id ?>">+</button>
                      </div>
                      <button type="submit" name="actualizar_cantidad" class="btn update-btn">
                        <i class="fas fa-sync-alt"></i> Actualizar
                      </button>
                    </form>
                  </td>
                  <td data-label="Subtotal" class="cart-product-subtotal" id="subtotal-<?= $id ?>">
                    S/ <?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                  </td>
                  <td data-label="Acciones">
                    <form method="POST" class="delete-form">
                      <input type="hidden" name="id_producto" value="<?= $id ?>">
                      <button type="submit" name="eliminar" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          
          <div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="index.php" class="btn">
              <i class="fas fa-arrow-left"></i> Seguir Comprando
            </a>
            <form method="POST" action="vaciar_carrito.php" style="display: inline;">
              <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Vaciar Carrito
              </button>
            </form>
          </div>
        </div>

        <div class="cart-summary">
          <h3 class="summary-title">Resumen de Compra</h3>
          
          <div class="summary-item">
            <span>Subtotal (<?= $count ?> productos):</span>
            <span id="summary-subtotal">S/ <?= number_format($subtotal, 2) ?></span>
          </div>
          
          <div class="summary-item">
            <span>IGV (18%):</span>
            <span id="summary-igv">S/ <?= number_format($igv, 2) ?></span>
          </div>
          
          <div class="summary-item">
            <span>Envío:</span>
            <span id="summary-envio"><?= $envio == 0 ? "Gratis" : "S/ ".number_format($envio,2) ?></span>
          </div>
          
          <?php if ($envio == 0): ?>
            <div class="free-shipping">
              <i class="fas fa-truck"></i>
              <span>¡Felicidades! Tienes envío gratis</span>
            </div>
          <?php else: ?>
            <div class="shipping-notice">
              <i class="fas fa-info-circle"></i>
              <span>Faltan S/ <span id="shipping-difference"><?= number_format(200 - $subtotal, 2) ?></span> para envío gratis</span>
            </div>
          <?php endif; ?>
          
          <div class="summary-total">
            <span>Total: S/ <?= number_format($total, 2) ?></span>
          </div>
          
          <button id="proceed-to-checkout" class="btn btn-success" style="width: 100%; margin-top: 20px;">
            <i class="fas fa-credit-card"></i> Proceder al Pago
          </button>
        </div>
      </div>

      <div class="checkout-form" id="checkout">
        <h3 class="form-title">Datos de Envío y Pago</h3>
        <form method="POST" action="procesar_pedido.php" id="checkout-form">
          <div class="form-grid">
            <div class="form-group">
              <label for="nombres">Nombres *</label>
              <input type="text" id="nombres" name="nombres" placeholder="Ingresa tus nombres" required>
            </div>
            
            <div class="form-group">
              <label for="apellidos">Apellidos *</label>
              <input type="text" id="apellidos" name="apellidos" placeholder="Ingresa tus apellidos" required>
            </div>
            
            <div class="form-group">
              <label for="email">Correo electrónico *</label>
              <input type="email" id="email" name="email" placeholder="Ingresa tu correo electrónico" required>
            </div>
            
            <div class="form-group">
              <label for="telefono">Teléfono *</label>
              <input type="text" id="telefono" name="telefono" placeholder="Ingresa tu teléfono" required>
            </div>
            
            <div class="form-group form-group-full">
              <label for="direccion">Dirección *</label>
              <input type="text" id="direccion" name="direccion" placeholder="Ingresa tu dirección completa" required>
            </div>
            
            <div class="form-group">
              <label for="departamento">Departamento *</label>
              <select id="departamento" name="departamento" required>
                <option value="">Seleccionar</option>
                <?php foreach ($peru_ubicaciones as $departamento => $provincias): ?>
                  <option value="<?= $departamento ?>"><?= $departamento ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label for="provincia">Provincia *</label>
              <select id="provincia" name="provincia" required>
                <option value="">Seleccionar</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="distrito">Distrito *</label>
              <select id="distrito" name="distrito" required>
                <option value="">Seleccionar</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="dni">DNI *</label>
              <input type="text" id="dni" name="dni" placeholder="Ingresa tu DNI" required pattern="[0-9]{8}">
            </div>
          </div>
          
          <h4 style="margin: 30px 0 20px; color: var(--neon-pink); font-family: var(--font-tech);">Método de Pago</h4>
          <div class="payment-methods">
            <div class="payment-method" data-method="tarjeta">
              <i class="fas fa-credit-card"></i>
              <div>Tarjeta de Crédito/Débito</div>
            </div>
            <div class="payment-method" data-method="paypal">
              <i class="fab fa-paypal"></i>
              <div>PayPal</div>
            </div>
            <div class="payment-method" data-method="transferencia">
              <i class="fas fa-university"></i>
              <div>Transferencia Bancaria</div>
            </div>
          </div>
          <input type="hidden" name="metodo_pago" id="metodo_pago" required>
          
          <button type="submit" class="btn-checkout">
            <i class="fas fa-lock"></i>
            <span class="button-text">Finalizar Compra - S/ <?= number_format($total, 2) ?></span>
            <span class="loading-spinner" style="display: none;"></span>
          </button>
        </form>
      </div>
    <?php endif; ?>
  </main>

  <!-- Toast Notification -->
  <div class="toast" id="toast">
    <i class="fas fa-check-circle"></i>
    <span class="toast-message" id="toast-message"></span>
    <button class="toast-close">&times;</button>
  </div>



  <script>
    // Datos de departamentos, provincias y distritos del Perú
    const peruUbicaciones = <?= json_encode($peru_ubicaciones) ?>;

    // Funcionalidad para el botón de modo oscuro/claro
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('i');
    
    // Verificar si hay una preferencia guardada
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
      document.body.classList.add('light-mode');
      themeIcon.classList.remove('fa-moon');
      themeIcon.classList.add('fa-sun');
    }
    
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('light-mode');
      
      if (document.body.classList.contains('light-mode')) {
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
        localStorage.setItem('theme', 'light');
      } else {
        themeIcon.classList.remove('fa-sun');
        themeIcon.classList.add('fa-moon');
        localStorage.setItem('theme', 'dark');
      }
    });

    // Crear partículas flotantes
    function createParticles() {
      const particlesContainer = document.getElementById('particles');
      const particleCount = 50;
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 8 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 5) + 's';
        particlesContainer.appendChild(particle);
      }
    }

    // Inicializar partículas
    createParticles();

    // Funcionalidad para los botones de cantidad
    document.querySelectorAll('.quantity-btn').forEach(button => {
      button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        const input = document.querySelector(`.quantity-input[data-id="${productId}"]`);
        let value = parseInt(input.value);
        
        if (this.classList.contains('plus')) {
          input.value = value + 1;
          updateProductSubtotal(productId, input.value);
        } else if (this.classList.contains('minus') && value > 1) {
          input.value = value - 1;
          updateProductSubtotal(productId, input.value);
        }
        
        // Efecto de animación
        this.style.transform = 'scale(1.3)';
        setTimeout(() => {
          this.style.transform = 'scale(1)';
        }, 200);
      });
    });

    // Actualizar subtotal cuando se cambia manualmente la cantidad
    document.querySelectorAll('.quantity-input').forEach(input => {
      input.addEventListener('change', function() {
        const productId = this.getAttribute('data-id');
        updateProductSubtotal(productId, this.value);
      });
    });

    // Función para actualizar el subtotal de un producto
    function updateProductSubtotal(productId, quantity) {
      const price = parseFloat(document.querySelector(`.quantity-input[data-id="${productId}"]`).getAttribute('data-price'));
      const subtotal = price * quantity;
      document.getElementById(`subtotal-${productId}`).textContent = `S/ ${subtotal.toFixed(2)}`;
      
      // Recalcular el resumen completo
      updateCartSummary();
    }

    // Función para recalcular el resumen completo del carrito
    function updateCartSummary() {
      let newSubtotal = 0;
      let itemCount = 0;
      
      // Calcular nuevo subtotal y contar items
      document.querySelectorAll('.quantity-input').forEach(input => {
        const price = parseFloat(input.getAttribute('data-price'));
        const quantity = parseInt(input.value);
        newSubtotal += price * quantity;
        itemCount += quantity;
      });
      
      // Calcular IGV (18%)
      const newIgv = newSubtotal * 0.18;
      
      // Calcular envío
      const newEnvio = newSubtotal > 200 ? 0 : 15;
      
      // Calcular total
      const newTotal = newSubtotal + newIgv + newEnvio;
      
      // Actualizar DOM
      document.getElementById('summary-subtotal').textContent = `S/ ${newSubtotal.toFixed(2)}`;
      document.getElementById('summary-igv').textContent = `S/ ${newIgv.toFixed(2)}`;
      document.getElementById('summary-envio').textContent = newEnvio === 0 ? 'Gratis' : `S/ ${newEnvio.toFixed(2)}`;
      document.querySelector('.summary-total span').textContent = `Total: S/ ${newTotal.toFixed(2)}`;
      
      // Actualizar mensaje de envío gratis
      if (newEnvio === 0) {
        document.querySelector('.shipping-notice')?.remove();
        if (!document.querySelector('.free-shipping')) {
          const freeShippingDiv = document.createElement('div');
          freeShippingDiv.className = 'free-shipping';
          freeShippingDiv.innerHTML = '<i class="fas fa-truck"></i><span>¡Felicidades! Tienes envío gratis</span>';
          document.getElementById('summary-envio').parentNode.after(freeShippingDiv);
        }
      } else {
        document.querySelector('.free-shipping')?.remove();
        const shippingDifference = (200 - newSubtotal).toFixed(2);
        if (document.querySelector('.shipping-notice')) {
          document.getElementById('shipping-difference').textContent = shippingDifference;
        } else {
          const shippingNotice = document.createElement('div');
          shippingNotice.className = 'shipping-notice';
          shippingNotice.innerHTML = `<i class="fas fa-info-circle"></i><span>Faltan S/ <span id="shipping-difference">${shippingDifference}</span> para envío gratis</span>`;
          document.getElementById('summary-envio').parentNode.after(shippingNotice);
        }
      }
      
      // Actualizar badge del carrito
      document.querySelector('.cart-badge').textContent = itemCount;
      
      // Actualizar texto del botón de checkout
      const buttonText = document.querySelector('.button-text');
      if (buttonText) {
        buttonText.textContent = `Finalizar Compra - S/ ${newTotal.toFixed(2)}`;
      }
    }

    // Selección de método de pago
    document.querySelectorAll('.payment-method').forEach(method => {
      method.addEventListener('click', function() {
        document.querySelectorAll('.payment-method').forEach(m => {
          m.classList.remove('selected');
        });
        this.classList.add('selected');
        document.getElementById('metodo_pago').value = this.getAttribute('data-method');
      });
    });

    // Validación del formulario
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
      const selectedMethod = document.getElementById('metodo_pago').value;
      if (!selectedMethod) {
        e.preventDefault();
        showToast('Por favor, selecciona un método de pago', 'error');
        // Scroll to payment methods
        document.querySelector('.payment-methods').scrollIntoView({ 
          behavior: 'smooth', 
          block: 'center'
        });
        return;
      }
      
      // Mostrar spinner de carga
      const button = document.querySelector('.btn-checkout');
      const buttonText = button.querySelector('.button-text');
      const spinner = button.querySelector('.loading-spinner');
      
      buttonText.textContent = 'Procesando...';
      spinner.style.display = 'inline-block';
      button.disabled = true;
    });

    // Función para mostrar notificaciones toast
    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toast-message');
      
      toast.className = type === 'success' ? 'toast' : 'toast error';
      toastMessage.textContent = message;
      toast.classList.add('show');
      
      setTimeout(() => {
        toast.classList.remove('show');
      }, 5000);
    }

    // Cerrar toast manualmente
    document.querySelector('.toast-close').addEventListener('click', function() {
      document.getElementById('toast').classList.remove('show');
    });

    // Cargar provincias según departamento seleccionado
    document.getElementById('departamento').addEventListener('change', function() {
      const provinciaSelect = document.getElementById('provincia');
      const distritoSelect = document.getElementById('distrito');
      const departamento = this.value;
      
      // Limpiar opciones actuales
      provinciaSelect.innerHTML = '<option value="">Seleccionar</option>';
      distritoSelect.innerHTML = '<option value="">Seleccionar</option>';
      
      // Cargar provincias según el departamento seleccionado
      if (departamento && peruUbicaciones[departamento]) {
        Object.keys(peruUbicaciones[departamento]).forEach(provincia => {
          const option = document.createElement('option');
          option.value = provincia;
          option.textContent = provincia;
          provinciaSelect.appendChild(option);
        });
      }
    });

    // Cargar distritos según provincia seleccionada
    document.getElementById('provincia').addEventListener('change', function() {
      const distritoSelect = document.getElementById('distrito');
      const departamentoSelect = document.getElementById('departamento');
      const provincia = this.value;
      const departamento = departamentoSelect.value;
      
      // Limpiar opciones actuales
      distritoSelect.innerHTML = '<option value="">Seleccionar</option>';
      
      // Cargar distritos según la provincia seleccionada
      if (departamento && provincia && peruUbicaciones[departamento][provincia]) {
        peruUbicaciones[departamento][provincia].forEach(distrito => {
          const option = document.createElement('option');
          option.value = distrito;
          option.textContent = distrito;
          distritoSelect.appendChild(option);
        });
      }
    });

    // Mostrar toast si hay un mensaje en la URL o en la sesión PHP
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('message')) {
      showToast(decodeURIComponent(urlParams.get('message')));
    }
    
    // Mostrar mensaje desde PHP session si existe
    <?php if (isset($_SESSION['mensaje'])): ?>
      showToast("<?= $_SESSION['mensaje'] ?>");
      <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    // Animación de elementos al hacer scroll
    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = 1;
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, observerOptions);

    // Observar elementos con clase fade-in
    document.querySelectorAll('.cart-item-row').forEach(element => {
      element.style.opacity = 0;
      element.style.transform = 'translateY(20px)';
      element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      observer.observe(element);
    });

    // Mostrar formulario de checkout al hacer clic en "Proceder al Pago"
    document.getElementById('proceed-to-checkout').addEventListener('click', function() {
      const checkoutForm = document.getElementById('checkout');
      checkoutForm.classList.add('visible');
      
      // Hacer scroll suave al formulario
      checkoutForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>
</body>
</html>