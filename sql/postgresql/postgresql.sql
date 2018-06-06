CREATE TABLE ezleafletlocation (
  contentobject_attribute_id integer DEFAULT 0 NOT NULL,
  contentobject_version integer DEFAULT 0 NOT NULL,
  latitude double precision DEFAULT 0 NOT NULL,
  longitude double precision DEFAULT 0 NOT NULL,
  address varchar(150) default NULL
);

ALTER TABLE ONLY ezleafletlocation
    ADD CONSTRAINT ezleafletlocation_pkey PRIMARY KEY (contentobject_attribute_id, contentobject_version);

CREATE INDEX ezleaflet_latitude_longitude_key ON ezleafletlocation USING btree (latitude, longitude);


INSERT INTO ezleafletlocation (contentobject_attribute_id, latitude, longitude)
SELECT contentobject_attribute_id, latitude, longitude FROM ezgmaplocation;