USE verdant_haven;
INSERT INTO plants (id,name,category,price,old_price,stock,image_path,description,care_instructions,care_light,care_water,care_soil,watering_hours) VALUES
(1,'Ficus Rubber Plant','indoor',1200,1500,12,'assets/images/Ficus Rubber Plant.png','A glossy indoor plant for bright living spaces.','Place in bright indirect light. Water when the top soil is dry. Wipe leaves gently.','Indirect','Weekly','Moist mix',168),
(2,'Monstera Deliciosa','indoor',1800,2200,4,'assets/images/Monstera Deliciosa.png','A tropical plant with distinctive split leaves.','Provide filtered light, moderate humidity and a support pole. Water after the top layer dries.','Filtered','Weekly','Peat-rich',168),
(3,'Bougainvillea Bonsai','flower',2500,3100,8,'assets/images/Bougainvillea Bonsai.png','A sun-loving bonsai with vivid flowers.','Keep in full sun and well-draining soil. Water deeply when dry; prune after flowering.','Full sun','Twice weekly','Well-draining',96),
(4,'Snake Plant (Sansevieria)','indoor',650,800,15,'assets/images/Snake Plant (Sansevieria).png','A hardy plant for rooms with changing light.','Let the soil dry fully between waterings. Avoid standing water.','Low to bright','Twice monthly','Dry sandy',336),
(5,'Thai Guava Sapling','fruit',450,550,0,'assets/images/Thai Guava Sapling.png','A fruit sapling suited to sunny rooftops.','Give full sun, rich soil and regular water. Feed during active growth.','Full sun','Daily','Loamy',24),
(6,'Areca Palm','outdoor',1400,1750,5,'assets/images/Areca Palm.png','Feathery palm for balconies and patios.','Place in filtered light. Keep the root zone evenly moist and remove brown fronds.','Filtered','Twice weekly','Rich soil',96),
(7,'Pandanus','outdoor',350,450,5,'assets/images/Pandanus.png','Resilient tropical foliage for garden paths.','Use bright light and free-draining soil. Water regularly during heat.','Bright','Regular','Well-draining',120),
(8,'Rose','flower',300,400,5,'assets/images/rose.png','Classic red flowering rose bush.','Give at least six hours of sun. Keep soil evenly moist and remove spent blooms.','Direct','Even moisture','Rich loam',72)
ON DUPLICATE KEY UPDATE id=id;
